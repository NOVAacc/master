<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SelfAccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function master(string $slug): array
    {
        $user = User::factory()->create(['password' => 'Delete-test-123', 'password_initialized_at' => now()]);
        $master = MasterProfile::create(['user_id' => $user->id, 'slug' => $slug, 'display_name' => 'Test master', 'timezone' => 'Asia/Bishkek']);
        $service = $master->services()->create(['name' => 'Service', 'duration_minutes' => 60, 'price_som' => 500, 'is_active' => true, 'sort_order' => 0]);
        $appointment = $master->appointments()->create([
            'master_service_id' => $service->id, 'client_name' => 'Client', 'client_phone' => '996555111222',
            'service_name_snapshot' => 'Service', 'price_som' => 500, 'status' => 'confirmed',
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(),
        ]);
        Storage::disk('local')->put('profile-images/'.$master->id.'/photo.jpg', 'fixture');
        return [$user, $master, $appointment];
    }

    private function confirmation(User $user): array
    {
        return ['confirm_account_id' => (string) $user->id, 'confirm_deletion' => true, 'deletion_password' => 'Delete-test-123'];
    }

    public function test_account_id_is_available_and_does_not_change_with_account_details(): void
    {
        [$user, $master] = $this->master('stable-id');
        $id = $user->id;
        $this->actingAs($user)->get('/dashboard')->assertInertia(fn (Assert $page) => $page->where('auth.user.id', $id));
        $user->update(['name' => 'New name', 'email' => 'renamed@example.test']);
        $master->update(['slug' => 'new-page-address']);
        $this->get('/dashboard')->assertInertia(fn (Assert $page) => $page->where('auth.user.id', $id));
        $this->assertSame($id, $user->fresh()->id);
    }

    public function test_guest_cannot_delete_and_password_and_explicit_confirmations_are_required(): void
    {
        [$user, $master, $appointment] = $this->master('confirmation');
        $this->delete('/account', $this->confirmation($user))->assertRedirect('/login');
        $this->actingAs($user);
        $this->delete('/account', [])->assertSessionHasErrors(['confirm_account_id', 'confirm_deletion', 'deletion_password']);
        $this->delete('/account', [...$this->confirmation($user), 'confirm_deletion' => false])->assertSessionHasErrors('confirm_deletion');
        $this->delete('/account', [...$this->confirmation($user), 'deletion_password' => 'wrong'])->assertSessionHasErrors('deletion_password');
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('master_appointments', ['id' => $appointment->id]);
        Storage::disk('local')->assertExists('profile-images/'.$master->id.'/photo.jpg');
    }

    public function test_master_cannot_target_another_account(): void
    {
        [$user] = $this->master('requesting-master');
        [$other] = $this->master('other-master');
        $this->actingAs($user)->delete('/account', $this->confirmation($other))->assertSessionHasErrors('confirm_account_id');
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('users', ['id' => $other->id]);
    }

    public function test_self_deletion_removes_own_records_and_sessions_and_keeps_other_master(): void
    {
        [$user, $master, $appointment] = $this->master('delete-own');
        [$other, $otherMaster, $otherAppointment] = $this->master('keep-other');
        DB::table('sessions')->insert(['id' => 'other-device', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        DB::table('password_reset_tokens')->insert(['email' => $user->email, 'token' => 'test', 'created_at' => now()]);
        $this->actingAs($user)->withSession(['private_marker' => 'old-session'])->delete('/account', [
            ...$this->confirmation($user), 'user_id' => $other->id,
        ])->assertRedirect('/login')->assertSessionHas('success')->assertSessionMissing('private_marker');
        $this->assertGuest('web');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('master_profiles', ['id' => $master->id]);
        $this->assertDatabaseMissing('master_services', ['master_profile_id' => $master->id]);
        $this->assertDatabaseMissing('master_appointments', ['id' => $appointment->id]);
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->assertDatabaseHas('users', ['id' => $other->id]);
        $this->assertDatabaseHas('master_appointments', ['id' => $otherAppointment->id]);
        Storage::disk('local')->assertMissing('profile-images/'.$master->id.'/photo.jpg');
        Storage::disk('local')->assertExists('profile-images/'.$otherMaster->id.'/photo.jpg');
        $this->get('/m/delete-own')->assertNotFound();
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
