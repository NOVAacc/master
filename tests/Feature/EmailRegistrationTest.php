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

    public function test_registration_emails_password_and_supports_email_login(): void
    {
        config()->set('mail.default', 'smtp');
        config()->set('mail.mailers.smtp.username', 'no-reply@example.test');
        config()->set('mail.mailers.smtp.password', 'test-secret');
        config()->set('mail.from.address', 'no-reply@example.test');
        Mail::fake();
        Http::fake();
        $this->post('/register', ['name' => 'Айгүл', 'phone' => '0555123456', 'email' => 'Master@Example.test'])
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
        $password = '';
        $verifyUrl = '';
        Mail::assertSent(RegistrationPassword::class, function ($mail) use ($user, &$password, &$verifyUrl) {
            $password = $mail->temporaryPassword;
            $verifyUrl = $mail->verifyUrl;
            return $mail->hasTo($user->email) && $mail->name === 'Айгүл' && preg_match('/^(?=[A-Za-z0-9]{6}$)(?=[^0-9]*[0-9][^0-9]*$)(?=.*[a-z])(?=.*[A-Z])[A-Za-z0-9]+$/', $password) === 1 && Hash::check($password, $user->password);
        });
        Http::assertNothingSent();
        $this->get($verifyUrl)->assertRedirect('/login');
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->post('/login', ['identifier' => $user->email, 'password' => $password])->assertRedirect('/dashboard');
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
        $this->post('/register', ['name' => 'Айгүл', 'phone' => '0555123456', 'email' => 'new@example.test'])->assertSessionHasErrors('phone');
        $this->post('/register', ['name' => 'Айгүл', 'phone' => '0555987654', 'email' => 'EXISTING@example.test'])->assertSessionHasErrors('email');
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
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new TransportException('Expected response code 250 but got code "550", with message "550 5.7.1 rejected".'));
        $response = $this->post('/register', ['name' => 'Айгүл', 'phone' => '0555123456', 'email' => 'master@example.test']);
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Аккаунт не создан', session('errors')->first('email'));
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('master_profiles', 0);
        $this->assertGuest();
        Mail::fake();
        $this->post('/register', ['name' => 'Айгүл', 'phone' => '0555123456', 'email' => 'master@example.test'])
            ->assertRedirect('/login')
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('master_profiles', 1);
        Mail::assertSent(RegistrationPassword::class);
    }

    public function test_unconfigured_mail_and_invalid_fields_never_create_account(): void
    {
        Mail::fake();
        config()->set('mail.default', 'log');
        Log::spy();
        $this->post('/register', ['name' => 'Айгүл', 'phone' => '0555123456', 'email' => 'master@example.test'])->assertSessionHasErrors('email');
        $this->post('/register', ['name' => 'Айгүл', 'phone' => '123', 'email' => 'master@example.test'])->assertSessionHasErrors('phone');
        $this->post('/register', ['name' => 'Айгүл', 'phone' => '0555123456', 'email' => 'invalid'])->assertSessionHasErrors('email');
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

        $this->post('/register', ['name' => 'Айгүл', 'phone' => '0555123456', 'email' => 'master@example.test'])
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
        $body = (new RegistrationPassword('Айгүл', '996555123456', 'test-password', $url))->render();
        $this->assertStringContainsString('Здравствуйте, Айгүл!', $body);
        $this->assertStringContainsString('Подтвердить почту', $body);
        $this->assertStringContainsString('expires=123&amp;signature=abc', $body);
        $textBody = view('mail.registration-password-text', [
            'name' => 'Айгүл', 'phone' => '996555123456',
            'temporaryPassword' => 'test-password', 'verifyUrl' => $url,
        ])->render();
        $this->assertStringContainsString($url, $textBody);
        $this->assertStringNotContainsString('&amp;signature', $textBody);
    }
}
