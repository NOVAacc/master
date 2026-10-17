<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\User;
use App\Services\WorkingHours;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LunchBreakTest extends TestCase
{
    use RefreshDatabase;

    private function master(): MasterProfile
    {
        $user = User::factory()->create();
        return MasterProfile::create([
            'user_id' => $user->id,
            'slug' => 'lunchmaster',
            'display_name' => 'Мастер',
            'timezone' => 'Asia/Bishkek',
            'booking_enabled' => true,
            'auto_confirm' => false,
        ]);
    }

    public function test_lunch_can_be_saved_and_blocks_overlapping_slots(): void
    {
        $master = $this->master();
        $weekday = 1;
        $payload = ['hours' => [[
            'weekday' => $weekday,
            'starts_at' => '09:00',
            'ends_at' => '18:00',
            'break_starts_at' => '13:00',
            'break_ends_at' => '14:00',
        ]]];

        $this->actingAs($master->user)->post('/schedule', $payload)
            ->assertRedirect()->assertSessionHasNoErrors();

        $stored = DB::table('master_working_hours')->where('master_profile_id', $master->id)->first();
        $this->assertSame('13:00', substr($stored->break_starts_at, 0, 5));
        $this->assertSame('14:00', substr($stored->break_ends_at, 0, 5));

        $monday = CarbonImmutable::parse('2026-10-19 00:00:00', 'Asia/Bishkek');
        $contains = fn (string $start, string $end) => app(WorkingHours::class)->contains(
            $master,
            $monday->setTimeFromTimeString($start)->utc(),
            $monday->setTimeFromTimeString($end)->utc()
        );

        $this->assertTrue($contains('12:00', '13:00'));
        $this->assertFalse($contains('12:30', '13:30'));
        $this->assertFalse($contains('13:00', '13:30'));
        $this->assertFalse($contains('13:30', '14:30'));
        $this->assertTrue($contains('14:00', '15:00'));
    }

    public function test_lunch_must_be_inside_working_hours(): void
    {
        $master = $this->master();
        $this->actingAs($master->user)->post('/schedule', ['hours' => [[
            'weekday' => 1,
            'starts_at' => '09:00',
            'ends_at' => '18:00',
            'break_starts_at' => '17:30',
            'break_ends_at' => '19:00',
        ]]])->assertSessionHasErrors('hours.0.break_starts_at');

        $this->assertDatabaseCount('master_working_hours', 0);
    }

    public function test_lunch_can_be_removed_without_removing_workday(): void
    {
        $master = $this->master();
        $base = ['weekday' => 1, 'starts_at' => '09:00', 'ends_at' => '18:00'];
        $this->actingAs($master->user)->post('/schedule', [
            'hours' => [$base + ['break_starts_at' => '13:00', 'break_ends_at' => '14:00']],
        ])->assertSessionHasNoErrors();
        $this->post('/schedule', ['hours' => [$base + [
            'break_starts_at' => null, 'break_ends_at' => null,
        ]]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('master_working_hours', [
            'master_profile_id' => $master->id,
            'weekday' => 1,
            'break_starts_at' => null,
            'break_ends_at' => null,
        ]);
    }
}
