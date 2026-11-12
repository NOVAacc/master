<?php

namespace Tests\Feature;

use App\Mail\RegistrationPassword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationPasswordFlowTest extends TestCase
{
    use RefreshDatabase;

    private function configureMailer(): void
    {
        config()->set('mail.default', 'smtp');
        config()->set('mail.mailers.smtp.host', 'mail.example.test');
        config()->set('mail.mailers.smtp.username', 'test');
        config()->set('mail.mailers.smtp.password', 'test');
        config()->set('mail.from.address', 'hello@example.test');
        Mail::fake();
    }

    public function test_registration_uses_chosen_password_and_requires_email_verification(): void
    {
        $this->configureMailer();

        $this->post('/register', [
            'name' => 'Test Master',
            'phone' => '996700123456',
            'email' => 'master@example.test',
            'password' => 'abc!12',
            'password_confirmation' => 'abc!12',
        ])->assertRedirect('/login');

        $user = User::where('email', 'master@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('abc!12', $user->password));
        $this->assertNull($user->email_verified_at);

        $verificationUrl = null;
        Mail::assertSent(RegistrationPassword::class, function ($mail) use (&$verificationUrl) {
            $this->assertFalse(property_exists($mail, 'temporaryPassword'));
            $verificationUrl = $mail->verifyUrl;
            return str_contains($mail->verifyUrl, '/security/email/verify/');
        });

        $this->post('/login', ['identifier' => $user->email, 'password' => 'abc!12'])
            ->assertSessionHasErrors('identifier');
        $this->assertGuest();

        $this->assertNotNull($verificationUrl);
        $this->get($verificationUrl)->assertRedirect('/login');
        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->post('/login', ['identifier' => $user->email, 'password' => 'abc!12'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_short_and_invalid_characters_are_rejected(): void
    {
        $this->configureMailer();
        foreach (['abc12', 'пароль12', 'abc 123'] as $password) {
            $this->post('/register', [
                'name' => 'Test Master', 'phone' => '996700123456',
                'email' => 'master@example.test', 'password' => $password,
                'password_confirmation' => $password,
            ])->assertSessionHasErrors('password');
        }
        $this->assertDatabaseCount('users', 0);
    }

    public function test_unverified_user_can_request_a_new_activation_link(): void
    {
        $this->configureMailer();
        $this->post('/register', [
            'name' => 'Test Master', 'phone' => '996700123456',
            'email' => 'master@example.test', 'password' => 'abc!12',
            'password_confirmation' => 'abc!12',
        ])->assertRedirect('/login');
        Mail::fake();

        $this->post('/email/verification/resend', ['email' => 'master@example.test'])
            ->assertRedirect();
        Mail::assertSent(RegistrationPassword::class);
    }
}
