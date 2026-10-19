<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BackfillMasterClientsTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_command_supports_safe_dry_run(): void
    {
        $this->artisan('bosh:backfill-clients', ['--dry-run' => true])
            ->assertExitCode(0);
    }

    public function test_backfill_command_is_idempotent_with_no_legacy_records(): void
    {
        $this->artisan('bosh:backfill-clients')->assertExitCode(0);
        $this->artisan('bosh:backfill-clients')->assertExitCode(0);
        $this->assertDatabaseCount('master_clients', 0);
    }
}
