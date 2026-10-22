<?php

namespace Tests\Feature;

use App\Models\Administrator;
use App\Models\MasterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminAccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): void
    {
        $admin = Administrator::where('username', 'admin')->firstOrFail();
        $this->actingAs($admin, 'admin')->withSession([
            'admin_password_stamp' => hash('sha256', $admin->getAuthPassword()),
        ]);
    }

    private function fixture(string $slug): array
    {
        $user = User::factory()->create();
        $master = MasterProfile::create([
            'user_id' => $user->id, 'slug' => $slug, 'display_name' => 'Delete fixture',
            'timezone' => 'Asia/Bishkek', 'booking_enabled' => true, 'auto_confirm' => true,
        ]);
        $service = $master->services()->create([
            'name' => 'Service', 'duration_minutes' => 60, 'price_som' => 500,
            'is_active' => true, 'sort_order' => 0,
        ]);
        $times = ['created_at' => now(), 'updated_at' => now()];
        $clientId = DB::table('master_clients')->insertGetId([
            'master_profile_id' => $master->id, 'name' => 'Client', 'phone_digits' => '996555111222',
        ] + $times);
        $appointment = $master->appointments()->create([
            'master_service_id' => $service->id, 'master_client_id' => $clientId,
            'client_name' => 'Client', 'client_phone' => '996555111222',
            'service_name_snapshot' => 'Service', 'price_som' => 500,
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(),
            'status' => 'confirmed',
        ]);
        DB::table('master_notifications')->insert([
            'master_appointment_id' => $appointment->id, 'recipient' => 'client', 'phone' => '996555111222',
        ] + $times);
        DB::table('master_reminders')->insert([
            'master_appointment_id' => $appointment->id, 'notification_revision' => 1,
            'phone' => '996555111222', 'due_at' => now()->addDay(),
        ] + $times);
        DB::table('master_appointment_changes')->insert([
            'master_appointment_id' => $appointment->id, 'user_id' => $user->id,
            'before' => '{}', 'after' => '{}',
        ] + $times);
        DB::table('master_working_hours')->insert([
            'master_profile_id' => $master->id, 'weekday' => 1, 'starts_at' => '09:00', 'ends_at' => '18:00',
        ] + $times);
        DB::table('master_time_blocks')->insert([
            'master_profile_id' => $master->id, 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(),
        ] + $times);
        $groupId = DB::table('master_group_sessions')->insertGetId([
            'master_profile_id' => $master->id, 'master_service_id' => $service->id,
            'starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHour(),
            'capacity' => 5, 'service_name_snapshot' => 'Group', 'price_som' => 500,
        ] + $times);
        DB::table('master_group_participants')->insert([
            'master_group_session_id' => $groupId, 'client_name' => 'Participant',
            'client_phone' => '996555111222', 'phone_digits' => '996555111222',
        ] + $times);
        DB::table('sessions')->insert([
            'id' => 'fixture-'.$slug, 'user_id' => $user->id, 'payload' => '', 'last_activity' => time(),
        ]);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email, 'token' => 'fixture', 'created_at' => now(),
        ]);
        Storage::disk('local')->put('profile-images/'.$master->id.'/photo.jpg', 'fixture');
        return [$user, $master, $appointment, $groupId];
    }

    public function test_guest_and_master_cannot_delete_accounts(): void
    {
        $user = User::factory()->create();
        $this->get('/admin/users/'.$user->id.'/delete')->assertRedirect('/admin/login');
        $this->delete('/admin/users/'.$user->id)->assertRedirect('/admin/login');
        $this->actingAs($user, 'web')->delete('/admin/users/'.$user->id, [
            'confirm_user_id' => $user->id, 'confirm_deletion' => 1,
        ])->assertRedirect('/admin/login');
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_confirmation_page_is_read_only_and_deletion_requires_correct_confirmation(): void
    {
        $user = User::factory()->create();
        $this->signIn();
        $this->get('/admin/users/'.$user->id.'/delete')->assertOk()->assertSee($user->name);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->delete('/admin/users/'.$user->id)->assertSessionHasErrors(['confirm_user_id', 'confirm_deletion']);
        $this->delete('/admin/users/'.$user->id, [
            'confirm_user_id' => $user->id + 1, 'confirm_deletion' => 1,
        ])->assertSessionHasErrors('confirm_user_id');
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->delete('/admin/users/not-an-id')->assertNotFound();
    }

    public function test_deletion_cascades_only_target_account_and_removes_credentials_and_uploads(): void
    {
        Storage::fake('local');
        [$target, $master, $appointment, $groupId] = $this->fixture('delete-target');
        [$other, $otherMaster, $otherAppointment, $otherGroup] = $this->fixture('keep-other');
        $this->signIn();
        $this->get('/admin/users/'.$target->id.'/delete')->assertOk()->assertSee('Групповые занятия');
        $this->delete('/admin/users/'.$target->id, [
            'confirm_user_id' => $target->id, 'confirm_deletion' => 1,
        ])->assertRedirect('/admin/users')->assertSessionHas('success', 'Аккаунт и связанные данные удалены.');
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        $this->assertDatabaseMissing('master_profiles', ['id' => $master->id]);
        foreach (['master_services', 'master_clients', 'master_working_hours', 'master_time_blocks', 'master_appointments', 'master_group_sessions'] as $table) {
            $this->assertDatabaseMissing($table, ['master_profile_id' => $master->id]);
            $this->assertDatabaseHas($table, ['master_profile_id' => $otherMaster->id]);
        }
        foreach (['master_notifications', 'master_reminders', 'master_appointment_changes'] as $table) {
            $this->assertDatabaseMissing($table, ['master_appointment_id' => $appointment->id]);
            $this->assertDatabaseHas($table, ['master_appointment_id' => $otherAppointment->id]);
        }
        $this->assertDatabaseMissing('master_group_participants', ['master_group_session_id' => $groupId]);
        $this->assertDatabaseHas('master_group_participants', ['master_group_session_id' => $otherGroup]);
        $this->assertDatabaseMissing('sessions', ['user_id' => $target->id]);
        $this->assertDatabaseHas('sessions', ['user_id' => $other->id]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $target->email]);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $other->email]);
        Storage::disk('local')->assertMissing('profile-images/'.$master->id.'/photo.jpg');
        Storage::disk('local')->assertExists('profile-images/'.$otherMaster->id.'/photo.jpg');
        $this->assertDatabaseHas('administrators', ['username' => 'admin']);
        $this->get('/admin/users')->assertOk()->assertDontSee($target->email);
        $this->get('/m/'.$master->slug)->assertNotFound();
        $this->delete('/admin/users/'.$target->id, [
            'confirm_user_id' => $target->id, 'confirm_deletion' => 1,
        ])->assertNotFound();
    }

    public function test_account_without_profile_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $this->signIn();
        $this->delete('/admin/users/'.$user->id, [
            'confirm_user_id' => $user->id, 'confirm_deletion' => 1,
        ])->assertRedirect('/admin/users');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
