<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function loginPage()
    {
        return Inertia::render('Auth', ['register' => false]);
    }

    public function registerPage()
    {
        return Inertia::render('Auth', ['register' => true]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(10)],
            'slug' => ['required', 'regex:/^[a-z][a-z0-9-]{2,59}$/', 'unique:master_profiles'],
        ]);
        $user = DB::transaction(function () use ($data) {
            $user = User::create($data);
            MasterProfile::create(['user_id' => $user->id, 'display_name' => $data['name'], 'slug' => $data['slug']]);

            return $user;
        });
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (! Auth::attempt($data)) {
            throw ValidationException::withMessages(['email' => 'Неверный email или пароль.']);
        }
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
