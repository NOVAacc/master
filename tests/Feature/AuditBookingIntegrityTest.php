<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuditBookingIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_read_only_integrity_audit_reports_clean_empty_database(): void
    {
        $this->artisan('bosh:audit-bookings')
            ->expectsOutput('Group sessions over capacity: 0')
            ->expectsOutput('Mismatch between confirmed participants and reserved seats: 0')
            ->expectsOutput('Appointment links to another master client: 0')
            ->expectsOutput('Active group sessions overlapping individual appointments: 0')
            ->assertExitCode(0);
    }
}
