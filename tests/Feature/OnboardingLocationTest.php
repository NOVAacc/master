<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_update_onboarding_location(): void
    {
        $this->post('/onboarding/location', ['address' => 'Test'])->assertRedirect('/login');
    }

    public function test_master_can_save_own_address_and_2gis_link(): void
    {
        $user = User::factory()->create();
        $master = MasterProfile::create([
            'user_id' => $user->id,
            'slug' => 'locationtest',
            'display_name' => 'Test master',
        ]);

        $this->actingAs($user)->post('/onboarding/location', [
            'address' => 'Бишкек, ул. Киевская, 100',
            'address_url' => 'https://2gis.kg/bishkek/firm/123456789',
        ])->assertRedirect(route('dashboard'))->assertSessionHas('onboarding_completed', true);

        $this->assertSame('Бишкек, ул. Киевская, 100', $master->fresh()->address);
        $this->assertSame('https://2gis.kg/bishkek/firm/123456789', $master->fresh()->address_url);
    }

    public function test_invalid_or_unsafe_2gis_urls_are_rejected(): void
    {
        $user = User::factory()->create();
        $master = MasterProfile::create([
            'user_id' => $user->id,
            'slug' => 'anothermaster',
            'display_name' => 'Test master',
        ]);

        foreach ([
            'http://2gis.kg/bishkek',
            'https://2gis.kg.evil.example/firm/123',
            'https://example.com/firm/123',
            'https://user:pass@2gis.kg/firm/123',
        ] as $url) {
            $this->actingAs($user)->post('/onboarding/location', [
                'address' => 'Forbidden',
                'address_url' => $url,
            ])->assertSessionHasErrors('address_url');
        }

        $this->assertNull($master->fresh()->address);
        $this->assertNull($master->fresh()->address_url);
    }
}
