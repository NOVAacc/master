<?php

namespace Tests\Feature;

use App\Models\MasterAppointment;
use App\Models\MasterProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ManualBookingTest extends TestCase
{
    use RefreshDatabase;

    private function setupMaster(string $slug = 'manualmaster'): array
    {
        $user = User::factory()->create();
        $master = MasterProfile::create([
            'user_id' => $user->id, 'slug' => $slug, 'display_name' => 'Master',
            'phone' => '996555123456', 'timezone' => 'Asia/Bishkek',
            'booking_enabled' => false, 'auto_confirm' => false, 'is_demo' => false,
        ]);
        $service = $master->services()->create([
            'name' => 'Стрижка', 'description' => '', 'duration_minutes' => 60,
            'price_som' => 500, 'is_active' => true, 'sort_order' => 0,
        ]);
        $day = CarbonImmutable::now('Asia/Bishkek')->addDays(3)->startOfDay();
        DB::table('master_working_hours')->insert([
            'master_profile_id' => $master->id, 'weekday' => $day->isoWeekday(),
            'starts_at' => '09:00', 'ends_at' => '18:00',
            'break_starts_at' => '12:00', 'break_ends_at' => '13:00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return [$user, $master, $service, $day];
    }

    private function payload($service, $day): array
    {
        return ['service_id' => $service->id, 'starts_at' => $day->setTime(10, 0)->utc()->toIso8601String(),
            'client_name' => 'Айжан', 'client_phone' => '996555111222'];
    }

    public function test_master_can_create_confirmed_booking_when_public_booking_is_disabled(): void
    {
        [$user, $master, $service, $day] = $this->setupMaster();
        $this->actingAs($user)->post('/appointments', $this->payload($service, $day))
            ->assertSessionHasNoErrors()->assertRedirect('/dashboard?calendar_date='.$day->toDateString());
        $this->assertDatabaseHas('master_appointments', [
            'master_profile_id' => $master->id, 'client_name' => 'Айжан',
            'status' => 'confirmed', 'price_som' => 500, 'whatsapp_consent' => false,
        ]);
        $this->assertDatabaseHas('master_clients', ['master_profile_id' => $master->id, 'phone_digits' => '996555111222']);
        $this->assertDatabaseCount('master_notifications', 0);
    }

    public function test_existing_client_is_reused_without_duplicates(): void
    {
        [$user, $master, $service, $day] = $this->setupMaster();
        $clientId = DB::table('master_clients')->insertGetId([
            'master_profile_id' => $master->id, 'name' => 'Existing', 'phone_digits' => '996555111222',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $payload = $this->payload($service, $day);
        unset($payload['client_name'], $payload['client_phone']);
        $payload['client_id'] = $clientId;
        $this->actingAs($user)->post('/appointments', $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('master_appointments', ['master_client_id' => $clientId, 'client_name' => 'Existing']);
        $this->assertDatabaseCount('master_clients', 1);
    }

    public function test_client_search_and_selection_are_scoped_to_authenticated_master(): void
    {
        [$user, $master, $service, $day] = $this->setupMaster();
        [, $other] = $this->setupMaster('othermaster');
        $foreign = DB::table('master_clients')->insertGetId([
            'master_profile_id' => $other->id, 'name' => 'Private client', 'phone_digits' => '996555444555',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actingAs($user)->getJson('/appointments/clients?q=Private')->assertOk()->assertExactJson([]);
        $this->post('/appointments', $this->payload($service, $day) + ['client_id' => $foreign])->assertNotFound();
        $this->assertDatabaseCount('master_appointments', 0);
    }

    public function test_busy_and_lunch_times_are_not_offered_and_cannot_be_booked(): void
    {
        [$user, $master, $service, $day] = $this->setupMaster();
        $payload = $this->payload($service, $day);
        $this->actingAs($user)->post('/appointments', $payload)->assertSessionHasNoErrors();
        $this->post('/appointments', $payload)->assertSessionHasErrors('starts_at');
        $slots = $this->getJson('/appointments/slots?date='.$day->toDateString().'&service_id='.$service->id)
            ->assertOk()->json();
        $labels = array_column($slots, 'label');
        $this->assertContains('09:00', $labels);
        $this->assertContains('11:00', $labels);
        $this->assertNotContains('10:00', $labels);
        $this->assertNotContains('11:15', $labels);
        $this->assertNotContains('12:00', $labels);
        $this->post('/appointments', array_replace($payload, [
            'starts_at' => $day->setTime(12, 0)->utc()->toIso8601String(),
        ]))->assertSessionHasErrors('starts_at');
        $this->assertDatabaseCount('master_appointments', 1);
    }

    public function test_time_blocks_exclude_overlapping_slots(): void
    {
        [$user, $master, $service, $day] = $this->setupMaster();
        DB::table('master_time_blocks')->insert([
            'master_profile_id' => $master->id,
            'starts_at' => $day->setTime(10, 0)->utc(), 'ends_at' => $day->setTime(11, 0)->utc(),
            'reason' => 'Busy', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actingAs($user)->post('/appointments', $this->payload($service, $day))->assertSessionHasErrors('starts_at');
        $labels = array_column($this->getJson('/appointments/slots?date='.$day->toDateString().'&service_id='.$service->id)
            ->assertOk()->json(), 'label');
        $this->assertNotContains('10:00', $labels);
        $this->assertContains('11:00', $labels);
    }

    public function test_foreign_services_and_guest_access_are_rejected(): void
    {
        [$user, , , $day] = $this->setupMaster();
        [, , $otherService] = $this->setupMaster('foreignmaster');
        $this->getJson('/appointments/clients')->assertUnauthorized();
        $this->actingAs($user)->post('/appointments', $this->payload($otherService, $day))->assertNotFound();
        $this->getJson('/appointments/slots?date='.$day->toDateString().'&service_id='.$otherService->id)->assertNotFound();
        $this->assertDatabaseCount('master_appointments', 0);
    }
}
