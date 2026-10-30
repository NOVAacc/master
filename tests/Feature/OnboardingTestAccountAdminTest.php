<?php

namespace Tests\Feature;

use App\Models\Administrator;
use App\Models\MasterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OnboardingTestAccountAdminTest extends TestCase
{
    use RefreshDatabase;

    private function adminLogin(): void
    {
        $admin = Administrator::where('username', 'admin')->firstOrFail();
        $this->actingAs($admin, 'admin')->withSession([
            'admin_password_stamp' => hash('sha256', $admin->getAuthPassword()),
        ]);
    }

    public function test_only_admin_can_create_resettable_onboarding_test_account(): void
    {
        $this->post('/admin/users/onboarding-test')->assertRedirect('/admin/login');
        $this->assertDatabaseCount('users', 0);
        $this->adminLogin();
        $response = $this->post('/admin/users/onboarding-test')->assertRedirect('/admin/users');
        $credentials = session('test_onboarding_credentials');
        $this->assertNotEmpty($credentials['email']);
        $this->assertNotEmpty($credentials['password']);
        $user = User::where('email', $credentials['email'])->firstOrFail();
        $this->assertTrue(Hash::check($credentials['password'], $user->password));
        $master = MasterProfile::where('user_id', $user->id)->firstOrFail();
        $this->assertTrue($master->is_demo);
        $this->assertFalse($master->booking_enabled);
        $this->assertNull($master->industry);
        $this->assertNull($master->activity);
        $this->assertFalse($master->onboarding_industry_done);
        $master->update(['industry' => 'beauty', 'activity' => 'barber', 'onboarding_industry_done' => true]);
        $this->post('/admin/users/'.$user->id.'/onboarding-reset')->assertRedirect('/admin/users');
        $this->assertNull($master->fresh()->industry);
        $this->assertNull($master->fresh()->activity);
        $this->assertFalse($master->fresh()->onboarding_industry_done);
    }

    public function test_real_master_cannot_be_reset_via_test_action(): void
    {
        $this->adminLogin();
        $user = User::factory()->create();
        MasterProfile::create([
            'user_id' => $user->id, 'slug' => 'realmaster', 'display_name' => 'Мастер',
            'industry' => 'beauty', 'activity' => 'barber',
        ]);
        $this->post('/admin/users/'.$user->id.'/onboarding-reset')->assertForbidden();
        $this->assertSame('beauty', $user->fresh()->masterProfile?->industry ?? MasterProfile::where('user_id', $user->id)->value('industry'));
    }
}
