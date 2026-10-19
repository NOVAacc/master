<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_or_modify_private_client_records(): void
    {
        $this->get('/clients/1')->assertRedirect('/login');
        $this->put('/clients/1', ['notes' => 'private'])->assertRedirect('/login');
    }

    public function test_user_without_master_profile_cannot_access_client_details(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner)->get('/clients/1')->assertNotFound();
        $this->actingAs($owner)->put('/clients/1', ['notes' => 'private'])->assertNotFound();
    }

    public function test_client_id_must_be_numeric(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner)->get('/clients/not-a-number')->assertNotFound();
    }
}
