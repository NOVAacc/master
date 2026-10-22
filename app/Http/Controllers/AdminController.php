<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function loginPage()
    {
        return response()->view('admin.login')->header('Cache-Control', 'no-store, private');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['username' => 'required|string|max:100', 'password' => 'required|string|max:128']);
        $key = 'admin-login:'.hash('sha256', Str::lower($data['username']).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['username' => 'Слишком много попыток. Повторите через '.RateLimiter::availableIn($key).' сек.']);
        }
        if (!Auth::guard('admin')->attempt($data)) {
            RateLimiter::hit($key, 900);
            throw ValidationException::withMessages(['username' => 'Неверный логин или пароль.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put('admin_password_stamp', hash('sha256', Auth::guard('admin')->user()->getAuthPassword()));
        return redirect()->route('admin.users');
    }

    public function users(Request $request)
    {
        $data = $request->validate(['q' => 'nullable|string|max:100']);
        $search = trim($data['q'] ?? '');
        $query = User::query()->leftJoin('master_profiles as m', 'm.user_id', '=', 'users.id')
            ->select('users.id', 'users.name', 'users.phone', 'users.email', 'users.created_at',
                'users.email_verified_at', 'users.phone_verified_at', 'm.slug', 'm.display_name', 'm.booking_enabled');
        if ($search !== '') {
            // Literal substring search; % and _ are not SQL wildcards.
            $query->where(function ($q) use ($search) {
                foreach (['users.name', 'users.phone', 'users.email', 'm.display_name', 'm.slug'] as $column) {
                    $q->orWhereRaw('LOCATE(?, '.$column.') > 0', [$search]);
                }
            });
        }
        return view('admin.users', [
            'users' => $query->orderByDesc('users.id')->paginate(20)->withQueryString(),
            'search' => $search,
            'total' => User::count(),
            'registeredToday' => User::whereDate('created_at', today())->count(),
        ]);
    }

    public function settings()
    {
        return view('admin.settings');
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|string|max:128',
            'password' => 'required|string|min:10|max:128|confirmed|different:current_password',
        ]);
        $admin = Auth::guard('admin')->user();
        if (!Hash::check($data['current_password'], $admin->getAuthPassword())) {
            throw ValidationException::withMessages(['current_password' => 'Текущий пароль указан неверно.']);
        }
        $admin->password = $data['password'];
        $admin->remember_token = Str::random(60);
        $admin->save();
        $request->session()->regenerate();
        $request->session()->put('admin_password_stamp', hash('sha256', $admin->getAuthPassword()));
        return redirect()->route('admin.settings')->with('success', 'Пароль изменён. Другие сеансы администратора завершены.');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->forget('admin_password_stamp');
        $request->session()->regenerate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
