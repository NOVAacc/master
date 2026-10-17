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
        config()->set('services.nikita.login', 'demo');
        config()->set('services.nikita.password', 'demo-password');
        config()->set('services.nikita.sender', 'BOSH');
    }

    public function test_valid_sms_code_creates_master_and_logs_user_in(): void
    {
        $receivedCode = null;
        Http::fake(function ($request) use (&$receivedCode) {
            $body = $request->body();
            preg_match('/код входа (\\d{6})/u', $body, $match);
            $receivedCode = $match[1] ?? null;
            preg_match('/<id>([A-Z0-9]{12})<\\/id>/', $body, $matchId);
            $id = $matchId[1] ?? '';
            return Http::response('<response><id>'.$id.'</id><status>0</status></response>', 200);
        });

        $this->post('/auth/send-code', [
            'name' => 'Айзада', 'phone' => '+996 555 123 456', 'mode' => 'register',
        ])->assertSessionHasNoErrors();
        $this->assertNotNull($receivedCode);
        $this->assertDatabaseCount('users', 0);
        $this->post('/auth/verify-code', ['code' => '000000' === $receivedCode ? '111111' : '000000'])
            ->assertSessionHasErrors('code');
        $this->assertDatabaseCount('users', 0);
        $this->post('/auth/verify-code', ['code' => $receivedCode])->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', ['name' => 'Айзада', 'phone' => '996555123456']);
        $this->assertDatabaseCount('master_profiles', 1);
        $this->assertAuthenticated();
    }

    public function test_registration_does_not_create_a_user_when_sms_gateway_rejects(): void
    {
        Http::fake(['smspro.nikita.kg/api/message' =>
            Http::response('<?xml version="1.0"?><response><status>5</status></response>', 200),
        ]);
        $this->post('/auth/send-code', [
            'name' => 'Айзада', 'phone' => '+996 555 123 456', 'mode' => 'register',
        ])->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('users', 0);
        Http::assertSent(fn ($request) =>
            $request->url() === 'https://smspro.nikita.kg/api/message'
            && str_contains($request->body(), '<phone>996555123456</phone>')
            && str_contains($request->body(), '<sender>BOSH</sender>')
        );
    }

    public function test_missing_sms_configuration_blocks_registration_without_network_call(): void
    {
        config()->set('services.nikita.login', null);
        Http::fake();
        $this->post('/auth/send-code', [
            'name' => 'Айзада', 'phone' => '0555123456', 'mode' => 'register',
        ])->assertSessionHasErrors('phone');
        Http::assertNothingSent();
    }

    public function test_code_cannot_be_verified_without_sms_request(): void
    {
        $this->post('/auth/verify-code', ['code' => '123456'])->assertSessionHasErrors('code');
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
