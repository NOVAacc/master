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
            'industry' => 'leisure', 'activity' => 'sauna',
        ])->assertRedirect('/dashboard')->assertSessionHasNoErrors();
        $profile->refresh();
        $this->assertSame('Мастер', $profile->display_name);
        $this->assertSame('leisure', $profile->industry);
        $this->assertSame('sauna', $profile->activity);
        $this->assertTrue($profile->onboarding_industry_done);
    }

    public function test_starter_services_are_saved_once_without_overwriting_existing_data(): void
    {
        $user = User::factory()->create();
        $master = MasterProfile::create([
            'user_id' => $user->id, 'slug' => 'startertest', 'display_name' => 'Мастер',
            'industry' => 'beauty', 'activity' => 'barber',
        ]);
        $payload = ['services' => [
            ['name' => 'Стрижка', 'duration_minutes' => 45, 'price_som' => 700],
            ['name' => 'Борода', 'duration_minutes' => 30, 'price_som' => 400],
        ]];
        $this->actingAs($user)->post('/onboarding/services', $payload)->assertRedirect('/dashboard')->assertSessionHasNoErrors();
        $this->assertSame(2, $master->services()->count());
        $this->post('/onboarding/services', $payload)->assertRedirect('/dashboard');
        $this->assertSame(2, $master->services()->count());
        $this->assertSame('Стрижка', $master->services()->orderBy('id')->first()->name);
    }

    public function test_telegram_is_required_before_finishing_new_master_onboarding(): void
    {
        $user = User::factory()->create();
        $master = MasterProfile::create([
            'user_id' => $user->id, 'slug' => 'requiredtelegram', 'display_name' => 'Мастер',
        ]);
        $this->actingAs($user)->post('/onboarding/services', [
            'services' => [['name' => 'Услуга', 'duration_minutes' => 60, 'price_som' => 1000]],
        ])->assertRedirect('/dashboard');
        $this->assertTrue((bool) $master->fresh()->onboarding_telegram_required);
        $this->post('/onboarding/complete')->assertStatus(422);
        $this->assertTrue((bool) $master->fresh()->onboarding_telegram_required);
        // A Telegram webhook is the only flow that can set a connected chat.
        $master->forceFill(['telegram_chat_id' => '123456'])->save();
        $this->post('/onboarding/complete')->assertRedirect('/dashboard')->assertSessionHas('onboarding_completed', true);
        $this->assertFalse((bool) $master->fresh()->onboarding_telegram_required);
    }

    public function test_starter_services_validation_does_not_create_partial_data(): void
    {
        $user = User::factory()->create();
        $master = MasterProfile::create([
            'user_id' => $user->id, 'slug' => 'starterinvalid', 'display_name' => 'Мастер',
        ]);
        $this->actingAs($user)->post('/onboarding/services', ['services' => [
            ['name' => 'Услуга', 'duration_minutes' => 1, 'price_som' => -10],
        ]])->assertSessionHasErrors();
        $this->assertSame(0, $master->services()->count());
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
