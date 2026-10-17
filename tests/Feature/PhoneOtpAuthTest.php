<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PhoneOtpAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.nikita.otp_key', 'test-otp-key');
    }

    public function test_correct_five_digit_code_registers_master_only_after_verification(): void
    {
        Http::fake([
            'smspro.nikita.kg/api/otp/send' => Http::response(['status' => 0, 'token' => 'opaque-test-token'], 200),
            'smspro.nikita.kg/api/otp/verify' => function ($request) {
                return Http::response(['status' => $request['code'] === '12345' ? 0 : 14], 200);
            },
        ]);
        $this->post('/auth/send-code', [
            'name' => 'Айзада', 'phone' => '+996 555 123 456', 'mode' => 'register',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('users', 0);
        $this->post('/auth/verify-code', ['code' => '11111'])->assertSessionHasErrors('code');
        $this->assertDatabaseCount('users', 0);
        $this->post('/auth/verify-code', ['code' => '12345'])->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', ['name' => 'Айзада', 'phone' => '996555123456']);
        $this->assertDatabaseCount('master_profiles', 1);
        $this->assertAuthenticated();
        Http::assertSent(fn ($req) => $req->url() === 'https://smspro.nikita.kg/api/otp/send'
            && $req['phone'] === '996555123456' && $req->hasHeader('X-API-KEY', 'test-otp-key'));
    }

    public function test_gateway_rejection_never_creates_a_user(): void
    {
        Http::fake(['smspro.nikita.kg/api/otp/send' => Http::response(['status' => 5, 'description' => 'Sender or text are not set'], 200)]);
        $this->post('/auth/send-code', [
            'name' => 'Айзада', 'phone' => '+996 555 123 456', 'mode' => 'register',
        ])->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_no_key_blocks_registration_without_network_call(): void
    {
        config()->set('services.nikita.otp_key', null);
        Http::fake();
        $this->post('/auth/send-code', [
            'name' => 'Айзада', 'phone' => '0555123456', 'mode' => 'register',
        ])->assertSessionHasErrors('phone');
        Http::assertNothingSent();
    }

    public function test_code_cannot_be_verified_without_request(): void
    {
        $this->post('/auth/verify-code', ['code' => '12345'])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_invalid_phone_is_rejected(): void
    {
        Http::fake();
        $this->post('/auth/send-code', [
            'name' => 'Айзада', 'phone' => '12345', 'mode' => 'register',
        ])->assertSessionHasErrors('phone');
        Http::assertNothingSent();
    }
}
