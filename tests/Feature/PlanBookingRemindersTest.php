<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PlanBookingRemindersTest extends TestCase
{
    use RefreshDatabase;

    public function test_planner_can_dry_run_without_creating_or_sending_messages(): void
    {
        $this->artisan('bosh:plan-reminders', ['--dry-run' => true])
            ->assertExitCode(0);
        $this->assertDatabaseCount('master_reminders', 0);
    }

    public function test_running_planner_repeatedly_without_appointments_is_safe(): void
    {
        $this->artisan('bosh:plan-reminders')->assertExitCode(0);
        $this->artisan('bosh:plan-reminders')->assertExitCode(0);
        $this->assertDatabaseCount('master_reminders', 0);
    }
}
