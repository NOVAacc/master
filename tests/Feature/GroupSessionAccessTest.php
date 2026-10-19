<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GroupSessionAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_creation_requires_authentication(): void
    {
        $this->post('/group-sessions', [
            'service_id' => 1, 'starts_at_local' => '2026-11-01T11:00', 'capacity' => 8,
        ])->assertRedirect('/login');
    }

    public function test_registration_requires_valid_session_and_consent(): void
    {
        $this->post('/group-sessions/1/register', [
            'client_name' => 'Клиент', 'client_phone' => '996555123456', 'consent' => true,
        ])->assertNotFound();
    }

    public function test_capacity_must_be_between_two_and_one_hundred(): void
    {
        $this->actingAs(\App\Models\User::factory()->create())
            ->post('/group-sessions', [
                'service_id' => 1, 'starts_at_local' => '2026-11-01T11:00', 'capacity' => 101,
            ])->assertSessionHasErrors('capacity');
    }
}
