<?php

namespace Tests\Feature;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Administrator
    {
        $admin = Administrator::where('username', 'admin')->firstOrFail();
        $admin->password = 'Test-admin-password!';
        $admin->save();
        return $admin;
    }

    private function signIn(Administrator $admin): static
    {
        return $this->actingAs($admin, 'admin')->withSession([
            'admin_password_stamp' => hash('sha256', $admin->getAuthPassword()),
        ]);
    }

    public function test_guest_and_master_cannot_access_admin(): void
    {
        $this->get('/admin/users')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create(), 'web');
        $this->get('/admin/users')->assertRedirect('/admin/login');
        $this->get('/admin/settings')->assertRedirect('/admin/login');
        $this->post('/admin/password', [])->assertRedirect('/admin/login');
    }

    public function test_login_logout_and_separate_guard(): void
    {
        $admin = $this->admin();
        $this->post('/admin/login', ['username' => 'admin', 'password' => 'wrong'])->assertSessionHasErrors('username');
        $this->assertGuest('admin');
        $this->post('/admin/login', ['username' => 'admin', 'password' => 'Test-admin-password!'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertGuest('web');
        $this->get('/admin/users')->assertOk();
        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest('admin');
        $this->get('/admin/users')->assertRedirect('/admin/login');
    }

    public function test_dashboard_is_private_and_shows_correct_user_counts(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $admin = $this->admin();

        $todayUser = User::factory()->create();
        $oldUser = User::factory()->create(['created_at' => now()->subDays(2)]);
        $realMaster = \App\Models\MasterProfile::create([
            'user_id' => $todayUser->id, 'slug' => 'real-master', 'display_name' => 'Real master',
            'is_demo' => false,
        ]);
        \App\Models\MasterProfile::create([
            'user_id' => $oldUser->id, 'slug' => 'demo-master', 'display_name' => 'Demo master',
            'is_demo' => true,
        ]);

        $future = now('UTC')->addDays(1);
        foreach ([
            ['confirmed', $realMaster->id, $future->copy()],
            ['pending', $realMaster->id, $future->copy()->addHour()],
            ['cancelled', $realMaster->id, $future->copy()->addHours(2)],
            ['completed', $realMaster->id, $future->copy()->addHours(3)],
            ['confirmed', $realMaster->id, now('UTC')->subDay()],
        ] as [$status, $masterId, $start]) {
            \App\Models\MasterAppointment::create([
                'master_profile_id' => $masterId, 'client_name' => 'Test client',
                'client_phone' => '996700000000', 'starts_at' => $start,
                'ends_at' => $start->copy()->addMinutes(30), 'price_som' => 100,
                'service_name_snapshot' => 'Service', 'status' => $status,
            ]);
        }
        \App\Models\MasterAppointment::create([
            'master_profile_id' => \App\Models\MasterProfile::where('user_id', $oldUser->id)->value('id'),
            'client_name' => 'Demo client', 'client_phone' => '996700000001',
            'starts_at' => $future->copy()->addHours(4),
            'ends_at' => $future->copy()->addHours(5),
            'price_som' => 100, 'service_name_snapshot' => 'Test service', 'status' => 'confirmed',
        ]);

        $this->signIn($admin)->get('/admin')->assertOk()
            ->assertViewIs('admin.dashboard')
            ->assertViewHas('totalUsers', 2)
            ->assertViewHas('registeredToday', 1)
            ->assertViewHas('upcomingAppointments', 2)
            ->assertSee('Всего пользователей')
            ->assertSee('Актуальные записи клиентов');
    }

    public function test_users_are_paginated_searchable_and_passwords_never_rendered(): void
    {
        $admin = $this->admin();
        User::factory()->count(21)->create();
        $user = User::factory()->create(['name' => 'Unique Master', 'email' => 'unique@example.com']);
        $this->signIn($admin)->get('/admin/users?q=unique@example.com')->assertOk()
            ->assertSee('Unique Master')->assertDontSee($user->password, false)
            ->assertViewHas('users', fn ($users) => $users->total() === 1);
        $this->get('/admin/users')->assertViewHas('users', fn ($users) => $users->count() === 20 && $users->total() === 22);
        $this->get('/admin/users?q=%')->assertViewHas('users', fn ($users) => $users->total() === 0);
        $this->get('/admin/users?q='.str_repeat('a', 101))->assertSessionHasErrors('q');
    }

    public function test_password_requires_current_password_and_confirmation(): void
    {
        $admin = $this->admin();
        $this->signIn($admin)->post('/admin/password', [
            'current_password' => 'incorrect', 'password' => 'New-admin-password!', 'password_confirmation' => 'New-admin-password!',
        ])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('Test-admin-password!', $admin->fresh()->password));
        $this->post('/admin/password', [
            'current_password' => 'Test-admin-password!', 'password' => 'New-admin-password!', 'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');
        $this->post('/admin/password', [
            'current_password' => 'Test-admin-password!', 'password' => 'New-admin-password!', 'password_confirmation' => 'New-admin-password!',
        ])->assertRedirect('/admin/settings');
        $this->assertTrue(Hash::check('New-admin-password!', $admin->fresh()->password));
        $this->get('/admin/settings')->assertOk();
    }

    public function test_password_change_invalidates_older_admin_sessions(): void
    {
        $admin = $this->admin();
        $oldStamp = hash('sha256', $admin->getAuthPassword());
        $admin->password = 'Updated-password!';
        $admin->save();
        $this->actingAs($admin, 'admin')->withSession(['admin_password_stamp' => $oldStamp])
            ->get('/admin/users')->assertRedirect('/admin/login');
        $this->assertGuest('admin');
    }

    public function test_five_invalid_logins_are_rate_limited(): void
    {
        $this->admin();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', ['username' => 'admin', 'password' => 'wrong'])->assertSessionHasErrors('username');
        }
        $this->post('/admin/login', ['username' => 'admin', 'password' => 'Test-admin-password!'])
            ->assertSessionHasErrors('username');
        $this->assertGuest('admin');
    }

    public function test_instagram_setting_is_admin_only_and_shared_with_site(): void
    {
        $this->get('/')->assertInertia(fn (\Inertia\Testing\AssertableInertia $p) => $p->where('site.instagramUsername', 'bosh.kgz'));
        $this->post('/admin/settings/social', ['instagram_username' => 'unauthorized'])->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create(), 'web')
            ->post('/admin/settings/social', ['instagram_username' => 'unauthorized'])->assertRedirect('/admin/login');
        $this->signIn($this->admin());
        $this->get('/admin/settings')->assertOk()->assertSee('bosh.kgz');
        $this->post('/admin/settings/social', ['instagram_username' => ' @bosh.new '])
            ->assertSessionHasNoErrors()->assertRedirect('/admin/settings');
        $this->assertDatabaseHas('site_settings', ['key' => 'instagram_username', 'value' => 'bosh.new']);
        $this->get('/')->assertInertia(fn (\Inertia\Testing\AssertableInertia $p) => $p->where('site.instagramUsername', 'bosh.new'));
        $this->get('/admin/settings')->assertSee('bosh.new');
    }

    public function test_instagram_setting_rejects_urls_and_invalid_names_without_overwriting_value(): void
    {
        $this->signIn($this->admin());
        foreach (['https://evil.example/name', 'bosh/new', 'bosh..new', '.bosh', 'bosh.', '', str_repeat('a', 31)] as $value) {
            $this->post('/admin/settings/social', ['instagram_username' => $value])->assertSessionHasErrors('instagram_username');
        }
        $this->assertDatabaseHas('site_settings', ['key' => 'instagram_username', 'value' => 'bosh.kgz']);
    }
}
