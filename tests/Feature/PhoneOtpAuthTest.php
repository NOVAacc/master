<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PhoneOtpAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_sms_routes_cannot_authorize_a_user(): void
    {
        Http::fake();
        $this->post('/auth/send-code', [
            'phone' => '996555123456', 'mode' => 'login',
        ])->assertNotFound();
        $this->post('/auth/verify-code', ['code' => '12345'])->assertNotFound();
        $this->assertGuest();
        Http::assertNothingSent();
    }
}
