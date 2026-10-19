<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Services\BookAppointment;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoMastersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TestMasterBookingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_the_nine_seeded_profiles_are_enabled_and_accept_pending_bookings(): void
    {
        Storage::fake('local');
        $this->seed(DemoMastersSeeder::class);
        $migration = require database_path('migrations/2026_10_19_000002_enable_test_master_bookings.php');
        $migration->up();
        $migration->up();
        $masters = MasterProfile::where('slug', 'like', 'demo-%')->get();
        $this->assertCount(9, $masters);
        foreach ($masters as $master) {
            $this->assertFalse($master->is_demo);
            $this->assertTrue($master->booking_enabled);
            $this->assertSame('996555000000', $master->phone);
            $this->assertFalse($master->whatsapp_notifications);
            $this->assertSame(4, $master->services()->count());
        }
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-19 00:00:00', 'UTC'));
        try {
            $master = $masters->first();
            $booking = app(BookAppointment::class)->create($master, $master->services()->firstOrFail(),
                CarbonImmutable::parse('2026-10-20 04:00:00', 'UTC'), 'Тестовая заявка', '996555000000');
            $this->assertSame('pending', $booking->status);
            $this->assertSame($master->id, $booking->master_profile_id);
            $this->assertDatabaseCount('master_appointments', 1);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }
}
