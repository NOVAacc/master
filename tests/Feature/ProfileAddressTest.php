<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_can_save_share_and_remove_a_2gis_address_link(): void
    {
        $user = User::factory()->create();
        $master = MasterProfile::create(['user_id' => $user->id, 'slug' => 'mapmaster', 'display_name' => 'Мастер', 'timezone' => 'Asia/Bishkek']);
        $fields = ['display_name' => 'Мастер', 'timezone' => 'Asia/Bishkek', 'booking_enabled' => true, 'auto_confirm' => false, 'address' => 'Бишкек, Чуй 1'];
        $this->actingAs($user);
        foreach (['https://2gis.kg/bishkek/firm/123', 'https://go.2gis.com/example'] as $url) {
            $this->post('/profile', [...$fields, 'address_url' => $url])->assertSessionHasNoErrors();
            $this->assertSame($url, $master->fresh()->address_url);
            $this->get('/m/mapmaster')->assertInertia(fn (Assert $page) => $page
                ->where('master.address', 'Бишкек, Чуй 1')->where('master.address_url', $url));
        }
        foreach (['https://2gis.kg.evil.test/map', 'https://example.com/map', 'javascript:alert(1)', 'https://evil.test@2gis.kg/map'] as $url) {
            $this->post('/profile', [...$fields, 'address_url' => $url])->assertSessionHasErrors('address_url');
            $this->assertSame('https://go.2gis.com/example', $master->fresh()->address_url);
        }
        $this->post('/profile', [...$fields, 'address_url' => ''])->assertSessionHasNoErrors();
        $this->assertNull($master->fresh()->address_url);
    }
}
