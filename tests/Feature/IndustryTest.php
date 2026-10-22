<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class IndustryTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_catalog_query_parameters_do_not_publish_master_records(): void
    {
        $industries = config('industries');
        $this->assertCount(6, $industries);
        $this->assertSame(56, array_sum(array_map(fn ($i) => count($i['activities']), $industries)));
        $this->profile('barbertarget', 'beauty', 'barber');
        foreach (['/?industry=beauty&activity=barber', '/?industry=medicine', '/?industry=medicine&activity=barber'] as $url) {
            $this->get($url)->assertOk()->assertInertia(fn (Assert $p) => $p
                ->component('Home')->missing('newMasters')->missing('topMasters')->missing('filters'))
                ->assertDontSee('barbertarget');
        }
    }

    public function test_profile_saves_only_matching_industry_activity_pairs_and_can_clear_them(): void
    {
        $master = $this->profile('mycategory', 'beauty', 'barber');
        $this->actingAs($master->user);
        $fields = ['display_name' => 'Мастер', 'timezone' => 'Asia/Bishkek', 'booking_enabled' => true, 'auto_confirm' => false];
        $this->post('/profile', [...$fields, 'industry' => 'education', 'activity' => 'tutoring'])->assertSessionHasNoErrors();
        $this->assertSame('tutoring', $master->fresh()->activity);
        $this->post('/profile', [...$fields, 'industry' => 'medicine', 'activity' => 'barber'])->assertSessionHasErrors('activity');
        $this->assertSame('education', $master->fresh()->industry);
        $this->post('/profile', [...$fields, 'industry' => '', 'activity' => ''])->assertSessionHasNoErrors();
        $this->assertNull($master->fresh()->industry);
        $this->assertNull($master->fresh()->activity);
    }

    private function profile(string $slug, string $industry, string $activity): MasterProfile
    {
        $m = MasterProfile::create(['user_id' => User::factory()->create()->id, 'slug' => $slug,
            'display_name' => 'Мастер', 'industry' => $industry, 'activity' => $activity, 'booking_enabled' => true]);
        $m->services()->create(['name' => 'Услуга', 'duration_minutes' => 30, 'price_som' => 1000, 'is_active' => true]);
        return $m;
    }
}
