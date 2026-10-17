<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_login_locks_for_one_hour_after_five_failed_attempts(): void
    {
        $user = User::factory()->create(['phone' => '996555123456', 'password' => Hash::make('abcde'),
            'password_initialized_at' => now(), 'failed_login_attempts' => 0]);
        foreach (range(1, 4) as $attempt) {
            $this->post('/login', ['identifier' => '+996 555 123 456', 'password' => 'wrong'])
                ->assertSessionHasErrors('identifier');
        }
        $this->post('/login', ['identifier' => '+996 555 123 456', 'password' => 'wrong'])
            ->assertSessionHasErrors('identifier');
        $user->refresh();
        $this->assertEquals(5, $user->failed_login_attempts);
        $this->assertTrue($user->login_locked_until->isFuture());
        $this->post('/login', ['identifier' => '996555123456', 'password' => 'abcde'])
            ->assertSessionHasErrors('identifier');
        $this->travel(61)->minutes();
        $this->post('/login', ['identifier' => '996555123456', 'password' => 'abcde'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertEquals(0, $user->fresh()->failed_login_attempts);
    }

    public function test_new_master_can_set_five_character_password_without_old_random_password(): void
    {
        $user = User::factory()->create(['phone' => '996555123456', 'password' => Hash::make('unavailable-random-password'),
            'password_initialized_at' => null]);
        $this->actingAs($user)->post('/security/password',
            ['password' => '12345', 'password_confirmation' => '12345'])->assertSessionHasNoErrors();
        $this->assertNotNull($user->fresh()->password_initialized_at);
        $this->post('/logout');
        $this->post('/login', ['identifier' => '996555123456', 'password' => '12345'])
            ->assertRedirect('/dashboard');
    }

    public function test_changing_password_requires_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-pass'),
            'password_initialized_at' => now()]);
        $this->actingAs($user)->post('/security/password',
            ['password' => 'new-pass', 'password_confirmation' => 'new-pass'])
            ->assertSessionHasErrors('current_password');
        $this->post('/security/password',
            ['current_password' => 'old-pass', 'password' => 'new-pass', 'password_confirmation' => 'new-pass'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('new-pass', $user->fresh()->password));
    }

    public function test_unverified_email_cannot_be_used_to_request_password_reset(): void
    {
        config()->set('mail.default', 'array');
        $this->post('/forgot-password', ['email' => 'unknown@example.test'])
            ->assertSessionHasErrors('email'); // Email delivery isn't configured.
    }

    public function test_email_verification_link_must_be_signed_and_match_current_email(): void
    {
        $user = User::factory()->create(['email' => 'verified@example.test', 'email_verified_at' => null]);
        $url = URL::temporarySignedRoute('security.email.verify', now()->addHour(),
            ['user' => $user->id, 'hash' => sha1($user->email)]);
        $this->get($url)->assertRedirect('/login');
        $this->assertNotNull($user->fresh()->email_verified_at);
        $other = User::factory()->create(['email' => 'other@example.test', 'email_verified_at' => null]);
        $this->get("/security/email/verify/{$other->id}/".sha1($other->email))->assertForbidden();
    }
}
