<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_contains_crawlable_content_metadata_and_safe_structured_data(): void
    {
        $response = $this->get('/?utm_source=instagram')->assertOk();
        $response->assertSee('rel="canonical" href="http://localhost"', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('<h1>Вы занимаетесь делом.', false)
            ->assertSee('SoftwareApplication', false);
        $this->assertStringNotContainsString('noindex', $response->getContent());
    }

    public function test_canonical_origin_is_independent_of_the_request_host(): void
    {
        config(['app.url' => 'https://bosh.kg']);
        $this->get('http://www.bosh.kg/?utm_source=test')->assertOk()
            ->assertSee('rel="canonical" href="https://bosh.kg"', false);
        $this->assertStringNotContainsString('www.bosh.kg', $this->get('/sitemap.xml')->streamedContent());
    }

    public function test_private_and_authentication_pages_are_not_indexable(): void
    {
        $this->get('/login')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, follow')
            ->assertSee('noindex, follow', false);
        $this->get('/not-a-real-page')->assertNotFound();
    }

    public function test_sitemap_only_contains_verified_active_non_demo_profiles(): void
    {
        $master = MasterProfile::create(['user_id' => User::factory()->create()->id,
            'slug' => 'verified-master', 'display_name' => 'Эрика <script>alert(1)</script>', 'booking_enabled' => true]);
        $master->services()->create(['name' => 'Маникюр', 'duration_minutes' => 60, 'price_som' => 800, 'is_active' => true, 'sort_order' => 1]);
        $demo = MasterProfile::create(['user_id' => User::factory()->create()->id,
            'slug' => 'demo-master', 'display_name' => 'Демо', 'booking_enabled' => true, 'is_demo' => true]);
        $demo->services()->create(['name' => 'Тест', 'duration_minutes' => 60, 'price_som' => 800, 'is_active' => true, 'sort_order' => 1]);
        $xml = $this->get('/sitemap.xml')->assertOk()->streamedContent();
        $this->assertStringContainsString('/m/verified-master', $xml);
        $this->assertStringNotContainsString('/m/demo-master', $xml);
        $this->assertNotFalse(simplexml_load_string($xml));
        $this->get('/m/verified-master?service='.$master->services()->firstOrFail()->id)->assertOk()
            ->assertSee('rel="canonical" href="http://localhost/m/verified-master"', false)
            ->assertSee('Услуги и цены')->assertSee('Маникюр')
            ->assertDontSee('<script>alert(1)</script>', false);
        $master->user->forceFill(['email_verified_at' => null])->save();
        $this->assertStringNotContainsString('/m/verified-master', $this->get('/sitemap.xml')->streamedContent());
        $this->get('/m/verified-master')->assertOk()->assertSee('noindex, follow', false);
    }
}
