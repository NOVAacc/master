<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

final class CustomerBookingLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_route_requires_valid_signature(): void
    {
        $this->get('/booking/manage/1?revision=1')->assertForbidden();
        $this->post('/booking/manage/1?revision=1', ['action' => 'cancel'])->assertForbidden();
    }

    public function test_expired_signed_url_is_rejected(): void
    {
        $url = URL::temporarySignedRoute('booking.manage', now()->subMinute(), [
            'appointment' => 1, 'revision' => 1,
        ]);
        $this->get($url)->assertForbidden();
    }

    public function test_signed_url_does_not_expose_other_appointments(): void
    {
        $url = URL::temporarySignedRoute('booking.manage', now()->addHour(), [
            'appointment' => 1, 'revision' => 1,
        ]);
        $this->get($url)->assertNotFound();
        $this->post($url, ['action' => 'cancel'])->assertNotFound();
    }
}
