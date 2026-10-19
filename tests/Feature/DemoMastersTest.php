<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\User;
use App\Services\BookAppointment;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoMastersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DemoMastersTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_profiles_are_complete_idempotent_and_cannot_accept_real_bookings(): void
    {
        Storage::fake('local');
        $real = MasterProfile::create(['user_id' => User::factory()->create()->id,
            'slug' => 'existing-master', 'display_name' => 'Настоящий мастер']);
        $this->seed(DemoMastersSeeder::class);
        $this->seed(DemoMastersSeeder::class);
        $this->assertSame(9, MasterProfile::where('is_demo', true)->count());
        $this->assertFalse($real->fresh()->is_demo);
        $this->assertSame('Настоящий мастер', $real->fresh()->display_name);
        $master = MasterProfile::where('is_demo', true)->firstOrFail();
        $this->assertSame(4, $master->services()->count());
        $this->assertFalse($master->booking_enabled);
        Storage::disk('local')->assertExists($master->avatar_path);
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Home')->has('newMasters', 9));
        $this->get('/m/'.$master->slug)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Booking')->where('master.is_demo', true)->has('services', 4)->has('slots', 0));
        // Even accidentally enabling a demo profile must not collect customer bookings.
        $master->update(['booking_enabled' => true]);
        try {
            app(BookAppointment::class)->create($master, $master->services()->firstOrFail(),
                CarbonImmutable::now('UTC')->addDays(2), 'Клиент', '996555000000');
            $this->fail('A demonstration profile accepted a real booking.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('booking', $error->errors());
        }
        $this->assertDatabaseCount('master_appointments', 0);
    }
}
