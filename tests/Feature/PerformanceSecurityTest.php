<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_calendar_loads_working_hours_once_for_many_slots(): void
    {
        $master = MasterProfile::create([
            'user_id' => User::factory()->create()->id,
            'slug' => 'querytest',
            'display_name' => 'Мастер',
            'timezone' => 'Asia/Bishkek',
            'booking_enabled' => true,
        ]);
        $master->services()->create([
            'name' => 'Услуга', 'duration_minutes' => 30,
            'price_som' => 500, 'is_active' => true,
        ]);
        $date = CarbonImmutable::now('Asia/Bishkek')->next(1)->toDateString();
        DB::table('master_working_hours')->insert([
            'master_profile_id' => $master->id, 'weekday' => 1,
            'starts_at' => '09:00', 'ends_at' => '18:00',
            'break_starts_at' => '13:00', 'break_ends_at' => '14:00',
        ]);

        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            if (str_contains(strtolower($query->sql), 'master_working_hours')) {
                $queries[] = $query->sql;
            }
        });
        $this->get('/m/querytest?date='.$date)->assertOk();

        // One weekday schedule lookup plus one available-days listing.
        // A query per 30-minute slot would create ~48 redundant reads.
        $this->assertLessThanOrEqual(2, count($queries), implode("\n", $queries));
    }

    public function test_responses_include_basic_security_headers(): void
    {
        $this->get('/')->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }
}
