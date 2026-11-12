<?php

namespace Tests\Feature;

use App\Mail\RegistrationPassword;
use App\Models\MasterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class EmailRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_emails_verification_link_and_supports_chosen_password_login(): void
    {
        config()->set('mail.default', 'smtp');
        config()->set('mail.mailers.smtp.username', 'no-reply@example.test');
        config()->set('mail.mailers.smtp.password', 'test-secret');
        config()->set('mail.from.address', 'no-reply@example.test');
        Mail::fake();
        Http::fake();
        $this->post('/register', ['name' => 'Айгүл', 'phone' => '0555123456', 'email' => 'Master@Example.test', 'password' => 'my!pass7', 'password_confirmation' => 'my!pass7'])
            ->assertRedirect('/login')->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertGuest();
        $user = User::where('phone', '996555123456')->firstOrFail();
        $this->assertSame('master@example.test', $user->email);
        $this->assertSame('Айгүл', $user->name);
        $this->assertSame('Айгүл', MasterProfile::firstOrFail()->display_name);
        $this->assertNull($user->phone_verified_at);
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->password_initialized_at);
        $this->assertSame($user->id, MasterProfile::firstOrFail()->user_id);
        $verifyUrl = '';
        $this->assertTrue(Hash::check('my!pass7', $user->password));
        Mail::assertSent(RegistrationPassword::class, function ($mail) use ($user, &$verifyUrl) {
            $verifyUrl = $mail->verifyUrl;
            return $mail->hasTo($user->email) && $mail->name === 'Айгүл' && ! property_exists($mail, 'temporaryPassword');
        });
        Http::assertNothingSent();
        $this->get($verifyUrl)->assertRedirect('/login');
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->post('/login', ['identifier' => $user->email, 'password' => 'my!pass7'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_does_not_replace_existing_credentials(): void
    {
        config()->set('mail.default', 'smtp');
        config()->set('mail.mailers.smtp.username', 'no-reply@example.test');
        config()->set('mail.mailers.smtp.password', 'test-secret');
        config()->set('mail.from.address', 'no-reply@example.test');
        Mail::fake();
        $user = User::factory()->create(['phone' => '996555123456', 'email' => 'existing@example.test', 'password' => Hash::make('original-password')]);
        $this->post('/register', ['name' => 'Айгүл', 'phone' => '0555123456', 'email' => 'new@example.test', 'password' => 'abc!123', 'password_confirmation' => 'abc!123'])->assertSessionHasErrors('phone');
        $this->post('/register', ['name' => 'Айгүл', 'phone' => '0555987654', 'email' => 'EXISTING@example.test', 'password' => 'abc!123', 'password_confirmation' => 'abc!123'])->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('original-password', $user->fresh()->password));
        $this->assertDatabaseCount('users', 1);
        Mail::assertNothingSent();
    }

    public function test_delivery_failure_rolls_back_account_and_profile(): void
    {
        config()->set('mail.default', 'smtp');
        config()->set('mail.mailers.smtp.username', 'no-reply@example.test');
        config()->set('mail.mailers.smtp.password', 'test-secret');
        config()->set('mail.from.address', 'no-reply@example.test');
        Log::shouldReceive('warning')->once()->withArgs(function (string $event, array $context): bool {
            return $event === 'registration_email_delivery_failed'
                && $context['exception'] === TransportException::class
                && $context['smtp_code'] === 550
                && $context['enhanced_status'] === '5.7.1'
                && $context['permanent_rejection'] === true;
        });
        Mail::shouldReceive('to')->twice()->andReturnSelf();
        Mail::shouldReceive('send')->once()->ordered()->andThrow(new TransportException('Expected response code 250 but got code "550", with message "550 5.7.1 rejected".'));
        Mail::shouldReceive('send')->once()->ordered()->andReturnNull();
        $response = $this->post('/register', ['name' => 'Айгүл', 'phone' => '0555123456', 'email' => 'master@example.test', 'password' => 'abc!123', 'password_confirmation' => 'abc!123']);
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Аккаунт не создан', session('errors')->first('email'));
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('master_profiles', 0);
        $this->assertGuest();
        $this->post('/register', ['name' => 'Айгүл', 'phone' => '0555123456', 'email' => 'master@example.test', 'password' => 'abc!123', 'password_confirmation' => 'abc!123'])
            ->assertRedirect('/login')
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('master_profiles', 1);
    }

    public function test_unconfigured_mail_and_invalid_fields_never_create_account(): void
    {
        Mail::fake();
        config()->set('mail.default', 'log');
        Log::spy();
        $this->post('/register', ['name' => 'Айгүл', 'phone' => '0555123456', 'email' => 'master@example.test', 'password' => 'abc!123', 'password_confirmation' => 'abc!123'])->assertSessionHasErrors('email');
        $this->post('/register', ['name' => 'Айгүл', 'phone' => '123', 'email' => 'master@example.test', 'password' => 'abc!123', 'password_confirmation' => 'abc!123'])->assertSessionHasErrors('phone');
        $this->post('/register', ['name' => 'Айгүл', 'phone' => '0555123456', 'email' => 'invalid', 'password' => 'abc!123', 'password_confirmation' => 'abc!123'])->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 0);
        Mail::assertNothingSent();
    }

    public function test_incomplete_smtp_configuration_never_creates_account_or_logs_secrets(): void
    {
        config()->set('mail.default', 'smtp');
        config()->set('mail.mailers.smtp.username', 'no-reply@example.test');
        config()->set('mail.mailers.smtp.password', null);
        Log::spy();
        Mail::fake();

        $this->post('/register', ['name' => 'Айгүл', 'phone' => '0555123456', 'email' => 'master@example.test', 'password' => 'abc!123', 'password_confirmation' => 'abc!123'])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 0);
        Mail::assertNothingSent();
        Log::shouldHaveReceived('warning')->with('registration_email_configuration_invalid', [
            'mailer' => 'smtp',
            'host_configured' => true,
            'username_configured' => true,
            'password_configured' => false,
            'from_configured' => true,
        ])->once();
    }

    public function test_sms_registration_cannot_bypass_email_registration(): void
    {
        Http::fake();
        $this->post('/auth/send-code', ['phone' => '0555123456', 'name' => 'Мастер', 'mode' => 'register'])
            ->assertNotFound();
        $this->assertDatabaseCount('users', 0);
        Http::assertNothingSent();
    }

    public function test_welcome_email_contains_usable_unescaped_verification_url(): void
    {
        $url = 'https://bosh.kg/security/email/verify/1/hash?expires=123&signature=abc';
        $body = (new RegistrationPassword('Айгүл', $url))->render();
        $this->assertStringContainsString('Здравствуйте, Айгүл!', $body);
        $this->assertStringContainsString('Подтвердить почту', $body);
        $this->assertStringContainsString('expires=123&amp;signature=abc', $body);
        $textBody = view('mail.registration-password-text', [
            'name' => 'Айгүл', 'verifyUrl' => $url,
        ])->render();
        $this->assertStringContainsString($url, $textBody);
        $this->assertStringNotContainsString('&amp;signature', $textBody);
    }
}
