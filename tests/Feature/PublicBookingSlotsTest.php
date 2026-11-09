<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBookingSlotsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_booking_rejects_times_not_shown_on_slot_grid(): void
    {
        $user = User::factory()->create();
        $master = MasterProfile::create([
            'user_id' => $user->id,
            'slug' => 'slot-test',
            'display_name' => 'Test master',
            'timezone' => 'Asia/Bishkek',
            'booking_enabled' => true,
        ]);
        $service = $master->services()->create([
            'name' => 'Service',
            'duration_minutes' => 30,
            'price_som' => 100,
            'is_active' => true,
        ]);

        foreach (['09:05', '09:15', '09:45'] as $time) {
            $date = CarbonImmutable::now('Asia/Bishkek')->addDays(2)->format('Y-m-d');
            $start = CarbonImmutable::parse($date.' '.$time, 'Asia/Bishkek')->toIso8601String();
            $this->post('/m/'.$master->slug.'/book', [
                'service_ids' => [$service->id],
                'starts_at' => $start,
                'client_name' => 'Client',
                'client_phone' => '996700123456',
                'consent' => true,
            ])->assertSessionHasErrors('starts_at');
        }

        $this->assertDatabaseCount('master_appointments', 0);
    }

    public function test_public_booking_rejects_dates_outside_calendar_window(): void
    {
        $user = User::factory()->create();
        $master = MasterProfile::create([
            'user_id' => $user->id,
            'slug' => 'window-test',
            'display_name' => 'Test master',
            'timezone' => 'Asia/Bishkek',
            'booking_enabled' => true,
        ]);
        $service = $master->services()->create([
            'name' => 'Service',
            'duration_minutes' => 30,
            'price_som' => 100,
            'is_active' => true,
        ]);

        $date = CarbonImmutable::now('Asia/Bishkek')->addDays(90)->format('Y-m-d');
        // Just after midnight on the next day in the same local timezone,
        // which is outside the visible 90-day booking calendar.
        $start = CarbonImmutable::parse($date.' 00:00', 'Asia/Bishkek')->addDay()->toIso8601String();
        $this->post('/m/'.$master->slug.'/book', [
            'service_ids' => [$service->id],
            'starts_at' => $start,
            'client_name' => 'Client',
            'client_phone' => '996700123456',
            'consent' => true,
        ])->assertSessionHasErrors('starts_at');

        $this->assertDatabaseCount('master_appointments', 0);
    }
}
