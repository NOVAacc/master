<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PhoneOtpAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.nikita.otp_key', 'fake-otp-key');
    }

    public function test_registration_creates_no_user_until_otp_is_verified(): void
    {
        Http::fake([
            'smspro.nikita.kg/api/otp/send' => Http::response(['status' => 0, 'token' => 'otp-token'], 200),
            'smspro.nikita.kg/api/otp/verify' => Http::response(['status' => 0, 'description' => 'Code Valid'], 200),
        ]);
        $this->post('/auth/send-code', ['name' => 'Айзада', 'phone' => '+996 555 123 456', 'mode' => 'register'])->assertRedirect();
        $this->assertDatabaseCount('users', 0);
        $this->post('/auth/verify-code', ['code' => '123456'])->assertRedirect('/dashboard');
        $user = User::where('phone', '996555123456')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('master_profiles', ['user_id' => $user->id, 'display_name' => 'Айзада']);
        Http::assertSentCount(2);
    }

    public function test_wrong_sms_code_does_not_create_user(): void
    {
        Http::fake([
            'smspro.nikita.kg/api/otp/send' => Http::response(['status' => 0, 'token' => 'otp-token'], 200),
            'smspro.nikita.kg/api/otp/verify' => Http::response(['status' => 14], 200),
        ]);
        $this->post('/auth/send-code', ['name' => 'Айзада', 'phone' => '0555123456', 'mode' => 'register'])->assertRedirect();
        $this->post('/auth/verify-code', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_sms_is_not_sent_without_configured_api_key(): void
    {
        config()->set('services.nikita.otp_key', null);
        Http::fake();
        $this->post('/auth/send-code', ['name' => 'Айзада', 'phone' => '0555123456', 'mode' => 'register'])
            ->assertSessionHasErrors('phone');
        Http::assertNothingSent();
    }

    public function test_login_requires_a_registered_phone(): void
    {
        Http::fake([
            'smspro.nikita.kg/api/otp/send' => Http::response(['status' => 0, 'token' => 'otp-token'], 200),
            'smspro.nikita.kg/api/otp/verify' => Http::response(['status' => 0], 200),
        ]);
        $this->post('/auth/send-code', ['phone' => '0555123456', 'mode' => 'login'])->assertRedirect();
        $this->post('/auth/verify-code', ['code' => '123456'])->assertSessionHasErrors('code');
        $this->assertGuest();
    }
}
