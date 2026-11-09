<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicMasterDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_is_available_without_signing_in_and_does_not_expose_account_records(): void
    {
        $user = User::factory()->create(['email' => 'private@example.test']);
        $response = $this->get('/demo');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Demo')
                ->missing('master')->missing('clients')->missing('appointments')->missing('services'))
            ->assertDontSee($user->email);
        $this->assertGuest();
    }

    public function test_demo_does_not_define_write_routes(): void
    {
        $this->post('/demo')->assertMethodNotAllowed();
        $this->put('/demo')->assertMethodNotAllowed();
        $this->delete('/demo')->assertMethodNotAllowed();
    }
}
