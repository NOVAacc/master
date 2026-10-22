<?php

namespace Tests\Feature;

use App\Models\MasterProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StoryAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private function setupStoryMaster(string $slug = 'story-master'): array
    {
        $user = User::factory()->create();
        $master = MasterProfile::create([
            'user_id' => $user->id,
            'slug' => $slug,
            'display_name' => 'Мастер',
            'phone' => '996555123456',
            'timezone' => 'Asia/Bishkek',
            'booking_enabled' => true,
            'auto_confirm' => false,
            'is_demo' => false,
        ]);
        $service = $master->services()->create([
            'name' => 'Стрижка',
            'duration_minutes' => 60,
            'price_som' => 700,
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $day = CarbonImmutable::now('Asia/Bishkek')->addDays(4)->startOfDay();
        DB::table('master_working_hours')->insert([
            'master_profile_id' => $master->id,
            'weekday' => $day->isoWeekday(),
            'starts_at' => '09:00',
            'ends_at' => '18:00',
            'break_starts_at' => '12:00',
            'break_ends_at' => '13:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $master, $service, $day];
    }

    public function test_free_slot_is_available_but_lunch_and_non_grid_time_are_not(): void
    {
        [$user, , $service, $day] = $this->setupStoryMaster();
        $base = ['service' => $service->id, 'date' => $day->toDateString()];
        $this->actingAs($user)->getJson('/stories/availability?'.http_build_query($base + ['time' => '10:00']))
            ->assertOk()->assertJsonPath('available', true);
        $this->getJson('/stories/availability?'.http_build_query($base + ['time' => '12:00']))
            ->assertOk()->assertJsonPath('available', false);
        $this->getJson('/stories/availability?'.http_build_query($base + ['time' => '10:15']))
            ->assertOk()->assertJsonPath('available', false);
    }

    public function test_time_block_prevents_story_and_accounts_are_isolated(): void
    {
        [$user, $master, $service, $day] = $this->setupStoryMaster();
        [, , $otherService] = $this->setupStoryMaster('other-story-master');
        $params = ['service' => $service->id, 'date' => $day->toDateString(), 'time' => '10:00'];
        DB::table('master_time_blocks')->insert([
            'master_profile_id' => $master->id,
            'starts_at' => $day->setTime(10, 0)->utc()->toDateTimeString(),
            'ends_at' => $day->setTime(11, 0)->utc()->toDateTimeString(),
            'reason' => 'Занято',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->actingAs($user)->getJson('/stories/availability?'.http_build_query($params))
            ->assertOk()->assertJsonPath('available', false);
        $this->getJson('/stories/availability?'.http_build_query(array_replace($params, ['service' => $otherService->id])))
            ->assertOk()->assertJsonPath('available', false);
    }

    public function test_story_style_is_saved_for_current_master_only(): void
    {
        [$user, $master] = $this->setupStoryMaster();
        [, $other] = $this->setupStoryMaster('another-story-master');
        $this->actingAs($user)->postJson('/stories/style', ['style' => 'premium'])
            ->assertOk()->assertJsonPath('style', 'premium');
        $this->assertDatabaseHas('master_profiles', ['id' => $master->id, 'story_style' => 'premium']);
        $this->assertDatabaseHas('master_profiles', ['id' => $other->id, 'story_style' => 'minimal']);
        $this->postJson('/stories/style', ['style' => 'invalid'])->assertUnprocessable();
    }
}
