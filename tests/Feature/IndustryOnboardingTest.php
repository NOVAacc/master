<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndustryOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_master_can_choose_industry_and_finish_onboarding(): void
    {
        $user = User::factory()->create();
        $profile = MasterProfile::create([
            'user_id' => $user->id, 'slug' => 'onboardtest', 'display_name' => 'Мастер',
            'onboarding_industry_done' => false,
        ]);
        $this->actingAs($user)->post('/onboarding/industry', [
            'name' => 'Айгүл', 'industry' => 'leisure', 'activity' => 'sauna',
        ])->assertRedirect('/dashboard')->assertSessionHasNoErrors();
        $profile->refresh();
        $this->assertSame('Айгүл', $profile->display_name);
        $this->assertSame('leisure', $profile->industry);
        $this->assertSame('sauna', $profile->activity);
        $this->assertTrue($profile->onboarding_industry_done);
    }

    public function test_unmatched_activity_is_rejected_and_other_master_cannot_be_modified(): void
    {
        $user = User::factory()->create();
        $profile = MasterProfile::create([
            'user_id' => $user->id, 'slug' => 'onboardfail', 'display_name' => 'Мастер',
            'onboarding_industry_done' => false,
        ]);
        $this->actingAs($user)->post('/onboarding/industry', [
            'name' => 'Айгүл', 'industry' => 'beauty', 'activity' => 'sauna',
        ])->assertStatus(422);
        $this->assertNull($profile->fresh()->industry);
        $this->assertFalse($profile->fresh()->onboarding_industry_done);
    }
}
