<?php

namespace Tests\Feature;

use App\Mail\RegistrationPassword;
use App\Models\MasterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_emails_password_and_preserves_phone_and_email_login(): void
    {
        config()->set('mail.default', 'smtp');
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
        $this->post('/login', ['identifier' => $user->phone, 'password' => $password])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout');
        $this->post('/login', ['identifier' => $user->email, 'password' => $password])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_does_not_replace_existing_credentials(): void
    {
        config()->set('mail.default', 'smtp');
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
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('SMTP unavailable'));
        $this->post('/register', ['name' => 'Айгүл', 'phone' => '0555123456', 'email' => 'master@example.test'])->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('master_profiles', 0);
        $this->assertGuest();
    }

    public function test_unconfigured_mail_and_invalid_fields_never_create_account(): void
    {
        Mail::fake();
        config()->set('mail.default', 'log');
        $this->post('/register', ['name' => 'Айгүл', 'phone' => '0555123456', 'email' => 'master@example.test'])->assertSessionHasErrors('email');
        $this->post('/register', ['name' => 'Айгүл', 'phone' => '123', 'email' => 'master@example.test'])->assertSessionHasErrors('phone');
        $this->post('/register', ['name' => 'Айгүл', 'phone' => '0555123456', 'email' => 'invalid'])->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 0);
        Mail::assertNothingSent();
    }

    public function test_sms_registration_cannot_bypass_email_registration(): void
    {
        Http::fake();
        $this->post('/auth/send-code', ['phone' => '0555123456', 'name' => 'Мастер', 'mode' => 'register'])
            ->assertSessionHasErrors('phone');
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
