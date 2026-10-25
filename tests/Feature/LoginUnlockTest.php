<?php

namespace Tests\Feature;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginUnlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_locked_account_returns_readable_error_even_when_captcha_is_required(): void
    {
        config()->set('services.turnstile.site_key', 'public-test-key');
        config()->set('services.turnstile.secret_key', 'secret-test-key');
        $user = User::factory()->create([
            'phone' => '996555123456',
            'password' => Hash::make('CorrectPassword!'),
            'failed_login_attempts' => 5,
            'login_locked_until' => now()->addHour(),
        ]);
        $this->post('/login', ['identifier' => $user->phone, 'password' => 'CorrectPassword!'])
            ->assertRedirect()->assertSessionHasErrors('identifier');
        $this->assertGuest();
        $this->assertSame(5, $user->fresh()->failed_login_attempts);
    }

    public function test_only_administrator_can_unlock_an_account_without_changing_its_password(): void
    {
        $user = User::factory()->create([
            'phone' => '996555123456',
            'password' => Hash::make('CorrectPassword!'),
            'failed_login_attempts' => 5,
            'login_locked_until' => now()->addHour(),
        ]);
        $url = '/admin/users/'.$user->id.'/unlock';
        $this->post($url)->assertRedirect('/admin/login');
        $this->actingAs($user)->post($url)->assertRedirect('/admin/login');
        $this->assertNotNull($user->fresh()->login_locked_until);

        $this->post('/logout');
        $admin = Administrator::where('username', 'admin')->firstOrFail();
        $this->actingAs($admin, 'admin')->withSession([
            'admin_password_stamp' => hash('sha256', $admin->getAuthPassword()),
        ]);
        $this->get('/admin/users')->assertOk()->assertSee('Снять блокировку входа');
        $hash = $user->password;
        $this->post($url)->assertRedirect()->assertSessionHas('success');
        $this->assertNull($user->fresh()->login_locked_until);
        $this->assertSame(0, $user->fresh()->failed_login_attempts);
        $this->assertSame($hash, $user->fresh()->password);
        $this->post('/admin/logout');
        $this->post('/login', ['identifier' => $user->phone, 'password' => 'CorrectPassword!'])
            ->assertRedirect('/dashboard');
    }
}
