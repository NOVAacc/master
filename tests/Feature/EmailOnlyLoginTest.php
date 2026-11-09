<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailOnlyLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_can_sign_in_with_email_and_password(): void
    {
        $user = User::factory()->create([
            'email' => 'master@example.com',
            'phone' => '996700123456',
        ]);

        $this->post('/login', [
            'identifier' => 'MASTER@EXAMPLE.COM',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_master_cannot_sign_in_with_phone_number(): void
    {
        User::factory()->create([
            'email' => 'master@example.com',
            'phone' => '996700123456',
        ]);

        $this->post('/login', [
            'identifier' => '996700123456',
            'password' => 'password',
        ])->assertSessionHasErrors('identifier');

        $this->assertGuest();
    }

    public function test_legacy_sms_login_endpoints_are_disabled(): void
    {
        $this->post('/auth/send-code', [])->assertNotFound();
        $this->post('/auth/verify-code', [])->assertNotFound();
    }
}
