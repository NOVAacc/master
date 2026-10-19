<?php

namespace Tests\Feature;

use App\Models\MasterAppointment;
use App\Models\MasterProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

final class BookingIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function master(string $slug): MasterProfile
    {
        $user = User::factory()->create();

        return MasterProfile::create([
            'user_id' => $user->id,
            'slug' => $slug,
            'display_name' => 'Test Master',
            'phone' => '996555123456',
            'timezone' => 'Asia/Bishkek',
            'booking_enabled' => true,
            'auto_confirm' => true,
            'is_demo' => false,
        ]);
    }

    private function appointment(MasterProfile $master): MasterAppointment
    {
        $starts = CarbonImmutable::now('UTC')->addDays(3)->startOfHour();
        return MasterAppointment::create([
            'master_profile_id' => $master->id,
            'client_name' => 'Tester',
            'client_phone' => '996555111222',
            'starts_at' => $starts->format('Y-m-d H:i:s'),
            'ends_at' => $starts->addHour()->format('Y-m-d H:i:s'),
            'service_name_snapshot' => 'Example',
            'price_som' => 100,
            'whatsapp_consent' => false,
            'status' => 'confirmed',
            'attendance_status' => 'expected',
            'payment_status' => null,
        ]);
    }

    public function test_stale_signed_customer_link_cannot_cancel_a_changed_appointment(): void
    {
        $appointment = $this->appointment($this->master('testmaster1'));
        $signed = URL::temporarySignedRoute('booking.manage', now()->addDay(), [
            'appointment' => $appointment->id, 'revision' => $appointment->revision,
        ]);

        $this->post($signed, ['action' => 'cancel'])->assertRedirect();
        $appointment->refresh();
        $this->assertSame('cancelled', $appointment->status);
        $this->assertSame(2, $appointment->revision);
        $this->post($signed, ['action' => 'cancel'])->assertNotFound();
        $this->assertSame('cancelled', $appointment->fresh()->status);
    }

    public function test_signed_customer_link_is_bound_to_exact_appointment_id(): void
    {
        $master = $this->master('testmaster2');
        $first = $this->appointment($master);
        $second = $this->appointment($master);
        $signed = URL::temporarySignedRoute('booking.manage', now()->addDay(), [
            'appointment' => $first->id, 'revision' => 1,
        ]);
        $tampered = str_replace('manage/'.$first->id, 'manage/'.$second->id, $signed);
        $this->get($tampered)->assertForbidden();
        $this->post($tampered, ['action' => 'cancel'])->assertForbidden();
        $this->assertSame('confirmed', $second->fresh()->status);
    }

    public function test_master_cannot_view_another_masters_client_history(): void
    {
        $first = $this->master('testmaster3');
        $second = $this->master('testmaster4');
        $clientId = DB::table('master_clients')->insertGetId([
            'master_profile_id' => $first->id,
            'phone_digits' => '996555888999',
            'name' => 'Private Name',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->actingAs($second->user)->get('/clients/'.$clientId)->assertNotFound();
        $this->actingAs($second->user)
            ->put('/clients/'.$clientId, ['notes' => 'Unauthorized'])
            ->assertNotFound();
        $this->assertDatabaseHas('master_clients', ['id' => $clientId, 'notes' => null]);
    }

    public function test_group_seat_counter_does_not_advance_for_a_duplicate_person(): void
    {
        $master = $this->master('testmaster5');
        $service = $master->services()->create([
            'name' => 'Group',
            'description' => '',
            'duration_minutes' => 60,
            'price_som' => 100,
            'is_active' => true,
        ]);
        $start = CarbonImmutable::now('UTC')->addDays(2)->startOfHour();
        $session = DB::table('master_group_sessions')->insertGetId([
            'master_profile_id' => $master->id,
            'master_service_id' => $service->id,
            'starts_at' => $start->format('Y-m-d H:i:s'),
            'ends_at' => $start->addHour()->format('Y-m-d H:i:s'),
            'capacity' => 2,
            'reserved_seats' => 0,
            'service_name_snapshot' => 'Group',
            'price_som' => 100,
            'status' => 'open',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $url = '/group-sessions/'.$session.'/register';
        $this->post($url, [
            'client_name' => 'One', 'client_phone' => '0555111222', 'consent' => true,
        ])->assertRedirect();
        $this->post($url, [
            'client_name' => 'One', 'client_phone' => '+996555111222', 'consent' => true,
        ])->assertSessionHasErrors('client_phone');
        $this->assertDatabaseHas('master_group_sessions', ['id' => $session, 'reserved_seats' => 1]);
        $this->assertDatabaseCount('master_group_participants', 1);
        $this->post($url, [
            'client_name' => 'Two', 'client_phone' => '996555111222', 'consent' => true,
        ])->assertRedirect();
        $this->assertDatabaseHas('master_group_sessions', ['id' => $session, 'reserved_seats' => 2]);
        $this->post($url, [
            'client_name' => 'Three', 'client_phone' => '996555333444', 'consent' => true,
        ])->assertSessionHasErrors('booking');
        $this->assertDatabaseCount('master_group_participants', 2);
    }
    public function test_group_session_blocks_overlapping_individual_booking(): void
    {
        $master = $this->master('testmaster6');
        $service = $master->services()->create([
            'name' => 'Training', 'description' => '', 'duration_minutes' => 60,
            'price_som' => 100, 'is_active' => true,
        ]);
        $local = CarbonImmutable::now($master->timezone)->addDays(3)->setTime(12, 0, 0);
        DB::table('master_working_hours')->insert([
            'master_profile_id' => $master->id,
            'weekday' => $local->isoWeekday(),
            'starts_at' => '09:00',
            'ends_at' => '18:00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $start = $local->utc();
        DB::table('master_group_sessions')->insert([
            'master_profile_id' => $master->id,
            'master_service_id' => $service->id,
            'starts_at' => $start->format('Y-m-d H:i:s'),
            'ends_at' => $start->addHour()->format('Y-m-d H:i:s'),
            'capacity' => 6,
            'reserved_seats' => 1,
            'service_name_snapshot' => $service->name,
            'price_som' => $service->price_som,
            'status' => 'open',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        try {
            app(\App\Services\BookAppointment::class)->create(
                $master, $service, $start, 'Tester', '996555987654'
            );
            $this->fail('An individual appointment was created over a group session.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('starts_at', $e->errors());
        }
        $this->assertDatabaseCount('master_appointments', 0);
    }

    public function test_group_participant_cancellation_is_idempotent_and_owner_scoped(): void
    {
        $owner = $this->master('testmaster7');
        $other = $this->master('testmaster8');
        $service = $owner->services()->create([
            'name' => 'Yoga', 'description' => '', 'duration_minutes' => 60,
            'price_som' => 200, 'is_active' => true,
        ]);
        $start = CarbonImmutable::now('UTC')->addDays(3)->startOfHour();
        $sessionId = DB::table('master_group_sessions')->insertGetId([
            'master_profile_id' => $owner->id, 'master_service_id' => $service->id,
            'starts_at' => $start->format('Y-m-d H:i:s'),
            'ends_at' => $start->addHour()->format('Y-m-d H:i:s'),
            'capacity' => 2, 'reserved_seats' => 1,
            'service_name_snapshot' => 'Yoga', 'price_som' => 200,
            'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $participantId = DB::table('master_group_participants')->insertGetId([
            'master_group_session_id' => $sessionId,
            'client_name' => 'Aida', 'client_phone' => '996555112233',
            'phone_digits' => '996555112233', 'status' => 'confirmed',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $url = "/group-sessions/{$sessionId}/participants/{$participantId}/cancel";

        $this->actingAs($other->user)->post($url)->assertNotFound();
        $this->assertDatabaseHas('master_group_sessions', [
            'id' => $sessionId, 'reserved_seats' => 1,
        ]);

        $this->actingAs($owner->user)->post($url)->assertRedirect();
        $this->assertDatabaseHas('master_group_participants', [
            'id' => $participantId, 'status' => 'cancelled',
        ]);
        $this->assertDatabaseHas('master_group_sessions', [
            'id' => $sessionId, 'reserved_seats' => 0,
        ]);

        $this->post($url)->assertRedirect();
        $this->assertDatabaseHas('master_group_sessions', [
            'id' => $sessionId, 'reserved_seats' => 0,
        ]);
    }

    public function test_owner_cannot_cancel_an_attendee_belonging_to_another_session(): void
    {
        $owner = $this->master('testmaster9');
        $service = $owner->services()->create([
            'name' => 'Dance', 'description' => '', 'duration_minutes' => 60,
            'price_som' => 100, 'is_active' => true,
        ]);
        $start = CarbonImmutable::now('UTC')->addDays(3)->startOfHour();
        $sessionIds = [];
        foreach ([0, 2] as $offset) {
            $sessionIds[] = DB::table('master_group_sessions')->insertGetId([
                'master_profile_id' => $owner->id,
                'master_service_id' => $service->id,
                'starts_at' => $start->addHours($offset)->format('Y-m-d H:i:s'),
                'ends_at' => $start->addHours($offset + 1)->format('Y-m-d H:i:s'),
                'capacity' => 2, 'reserved_seats' => 1,
                'service_name_snapshot' => 'Dance', 'price_som' => 100,
                'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $participantId = DB::table('master_group_participants')->insertGetId([
            'master_group_session_id' => $sessionIds[1],
            'client_name' => 'Wrong', 'client_phone' => '996555121212',
            'phone_digits' => '996555121212', 'status' => 'confirmed',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actingAs($owner->user)
            ->post("/group-sessions/{$sessionIds[0]}/participants/{$participantId}/cancel")
            ->assertNotFound();
        $this->assertDatabaseHas('master_group_participants', [
            'id' => $participantId, 'status' => 'confirmed',
        ]);
    }

}
