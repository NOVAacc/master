<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BusySlotVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_can_show_or_hide_busy_slots_without_allowing_booking_them(): void
    {
        $user = User::factory()->create();
        $master = MasterProfile::create([
            'user_id' => $user->id, 'slug' => 'busy-slot-test', 'display_name' => 'Test',
            'timezone' => 'Asia/Bishkek', 'booking_enabled' => true, 'auto_confirm' => true,
            'is_demo' => false,
        ]);
        $service = $master->services()->create([
            'name' => 'Test service', 'duration_minutes' => 30, 'price_som' => 500,
            'is_active' => true,
        ]);
        $local = CarbonImmutable::now('Asia/Bishkek')->addDays(3)->setTime(12, 0);
        DB::table('master_working_hours')->insert([
            'master_profile_id' => $master->id, 'weekday' => $local->isoWeekday(),
            'starts_at' => '09:00', 'ends_at' => '18:00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $start = $local->utc();
        $master->appointments()->create([
            'client_name' => 'Busy', 'client_phone' => '996555111222',
            'starts_at' => $start->format('Y-m-d H:i:s'),
            'ends_at' => $start->addMinutes(30)->format('Y-m-d H:i:s'),
            'service_name_snapshot' => 'Test service', 'price_som' => 500,
            'status' => 'confirmed',
        ]);
        $url = '/m/'.$master->slug.'?date='.$local->toDateString().'&service='.$service->id;
        $this->get($url)->assertInertia(fn (Assert $page) => $page->component('Booking')
            ->where('master.show_busy_slots', false)
            ->where('slots', fn ($slots) => collect($slots)->every(fn ($slot) => $slot['available'] === true)));

        $master->update(['show_busy_slots' => true]);
        $this->get($url)->assertInertia(fn (Assert $page) => $page->component('Booking')
            ->where('master.show_busy_slots', true)
            ->where('slots', fn ($slots) => collect($slots)->contains(fn ($slot) => $slot['label'] === '12:00' && $slot['available'] === false)));

        $master->update(['show_busy_slots' => false]);
        $this->get($url)->assertInertia(fn (Assert $page) => $page->component('Booking')
            ->where('slots', fn ($slots) => !collect($slots)->contains(fn ($slot) => $slot['label'] === '12:00')));
    }
}
