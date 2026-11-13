<?php
namespace Tests\Feature;
use App\Models\MasterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
class MasterModeSwitchTest extends TestCase {
    use RefreshDatabase;
    public function test_public_page_exposes_owner_mode_only_to_profile_owner(): void {
        $owner = User::factory()->create();
        $master = MasterProfile::create(['user_id' => $owner->id, 'slug' => 'mode-owner', 'display_name' => 'Мастер', 'timezone' => 'Asia/Bishkek']);
        $this->get('/m/mode-owner')->assertOk()->assertInertia(fn (Assert $page) => $page->where('isOwner', false));
        $this->actingAs($owner)->get('/m/mode-owner')->assertOk()->assertInertia(fn (Assert $page) => $page->where('isOwner', true));
        $this->actingAs(User::factory()->create())->get('/m/mode-owner')->assertOk()->assertInertia(fn (Assert $page) => $page->where('isOwner', false));
    }
}
