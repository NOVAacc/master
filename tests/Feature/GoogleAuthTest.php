<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.google', [
            'client_id' => 'testing-client',
            'client_secret' => 'testing-secret',
            'redirect' => 'http://localhost/auth/google/callback',
        ]);
    }

    private function initiate(): string
    {
        $response = $this->get('/auth/google');
        $response->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $params);
        $this->assertSame('testing-client', $params['client_id']);
        $this->assertSame('http://localhost/auth/google/callback', $params['redirect_uri']);
        return $params['state'];
    }

    private function googleIdentity(string $email, string $sub = 'google-test-sub'): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-access-token'], 200),
            'openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => $sub, 'email' => $email, 'email_verified' => true, 'name' => 'Тестовый мастер',
            ], 200),
        ]);
    }

    public function test_rejects_oauth_callback_without_valid_state(): void
    {
        $this->get('/auth/google/callback?code=abc&state=unrecognized')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_google_signup_collects_phone_before_creating_account(): void
    {
        $state = $this->initiate();
        $this->googleIdentity('newgoogle@example.com');
        $this->get('/auth/google/callback?code=abc&state='.$state)->assertRedirect('/auth/google/finish');
        $this->assertDatabaseMissing('users', ['email' => 'newgoogle@example.com']);
        $this->post('/auth/google/finish', ['phone' => '996700123456'])->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', ['email' => 'newgoogle@example.com', 'google_sub' => 'google-test-sub']);
        $this->assertAuthenticated();
    }

    public function test_existing_email_cannot_be_taken_over_via_google(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);
        $state = $this->initiate();
        $this->googleIdentity('existing@example.com');
        $this->get('/auth/google/callback?code=abc&state='.$state)->assertRedirect('/login');
        $this->assertGuest();
        $this->assertDatabaseHas('users', ['email' => 'existing@example.com', 'google_sub' => null]);
    }

    public function test_existing_account_can_link_google_only_when_authenticated(): void
    {
        $user = User::factory()->create(['email' => 'existing@example.com']);
        $this->actingAs($user);
        $state = $this->initiate();
        $this->googleIdentity('existing@example.com');
        $this->get('/auth/google/callback?code=abc&state='.$state)->assertRedirect('/dashboard');
        $this->assertSame('google-test-sub', $user->fresh()->google_sub);
    }

    public function test_google_login_respects_account_lock(): void
    {
        User::factory()->create([
            'email' => 'blocked@example.com', 'google_sub' => 'blocked-sub',
            'login_locked_until' => now()->addHour(),
        ]);
        $state = $this->initiate();
        $this->googleIdentity('blocked@example.com', 'blocked-sub');
        $this->get('/auth/google/callback?code=abc&state='.$state)->assertRedirect('/login');
        $this->assertGuest();
    }
}
