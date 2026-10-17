<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function loginPage() { return Inertia::render('Auth', ['register' => false]); }
    public function registerPage() { return Inertia::render('Auth', ['register' => true]); }

    private function phone(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw);
        if (strlen($digits) === 10 && str_starts_with($digits, '0')) $digits = '996'.substr($digits, 1);
        if (strlen($digits) === 9) $digits = '996'.$digits;
        if (!preg_match('/^996\d{9}$/', $digits)) {
            throw ValidationException::withMessages(['phone' => 'Введите номер Кыргызстана в формате +996 XXX XXX XXX.']);
        }
        return $digits;
    }

    public function sendCode(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'name' => ['nullable', 'string', 'min:2', 'max:100'],
            'mode' => ['required', 'in:login,register'],
        ]);
        $phone = $this->phone($data['phone']);
        if ($data['mode'] === 'register' && empty(trim($data['name'] ?? ''))) {
            throw ValidationException::withMessages(['name' => 'Введите имя.']);
        }
        $key = config('services.nikita.otp_key');
        if (!$key) {
            throw ValidationException::withMessages(['phone' => 'Сервис подтверждения SMS пока не настроен.']);
        }
        if ($request->session()->get('otp_sent_at', 0) > time() - 60) {
            throw ValidationException::withMessages(['phone' => 'Повторная отправка доступна через минуту.']);
        }
        $transaction = Str::random(24);
        try {
            $response = Http::withHeaders(['X-API-KEY' => $key])
                ->acceptJson()->timeout(10)
                ->post('https://smspro.nikita.kg/api/otp/send', [
                    'transaction_id' => $transaction,
                    'phone' => $phone,
                ]);
            $result = $response->json();
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['phone' => 'Сервис SMS временно недоступен.']);
        }
        if (!$response->successful() || !is_array($result)
            || (string) ($result['status'] ?? '') !== '0'
            || !is_string($result['token'] ?? null) || $result['token'] === '') {
            throw ValidationException::withMessages(['phone' => 'Не удалось отправить код. Проверьте настройки OTP, номер и баланс Nikita.']);
        }
        $request->session()->put('pending_otp', [
            'token' => encrypt($result['token']),
            'phone' => $phone,
            'name' => trim($data['name'] ?? ''),
            'mode' => $data['mode'],
            'expires_at' => time() + 180,
            'attempts' => 0,
        ]);
        $request->session()->put('otp_sent_at', time());
        return back()->with('otp_sent', true);
    }

    public function verifyCode(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'regex:/^[0-9]{5}$/']]);
        $pending = $request->session()->get('pending_otp');
        if (!$pending || ($pending['expires_at'] ?? 0) < time()) {
            throw ValidationException::withMessages(['code' => 'Срок действия кода истёк. Запросите новый.']);
        }
        if (($pending['attempts'] ?? 0) >= 5) {
            $request->session()->forget('pending_otp');
            throw ValidationException::withMessages(['code' => 'Слишком много попыток. Запросите новый код.']);
        }
        $pending['attempts']++;
        $request->session()->put('pending_otp', $pending);
        try {
            $response = Http::withHeaders(['X-API-KEY' => config('services.nikita.otp_key')])
                ->acceptJson()->timeout(10)
                ->post('https://smspro.nikita.kg/api/otp/verify', [
                    'token' => decrypt($pending['token']),
                    'code' => $data['code'],
                ]);
            $result = $response->json();
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['code' => 'Не удалось проверить код. Попробуйте ещё раз.']);
        }
        if (!$response->successful() || !is_array($result) || (string) ($result['status'] ?? '') !== '0') {
            throw ValidationException::withMessages(['code' => 'Неверный или просроченный код.']);
        }
        $user = DB::transaction(function () use ($pending) {
            $user = User::where('phone', $pending['phone'])->lockForUpdate()->first();
            if ($user) {
                if ($user->password_initialized_at) {
                    throw ValidationException::withMessages(['code' => 'Для этого аккаунта используйте вход по паролю. Если забыли пароль — восстановите его через почту.']);
                }
                return $user;
            }
            if ($pending['mode'] !== 'register') {
                throw ValidationException::withMessages(['code' => 'Аккаунт не найден. Сначала зарегистрируйтесь.']);
            }
            $user = User::create([
                'name' => $pending['name'],
                'phone' => $pending['phone'],
                'phone_verified_at' => now(),
                'password' => Hash::make(Str::random(48)),
            ]);
            // Short, non-sequential public addresses, e.g. bosh.kg/m/k8q2p4z.
            // Uniqueness is enforced again by the database unique index.
            do {
                $slug = strtolower(Str::random(7));
            } while (MasterProfile::where('slug', $slug)->exists());
            MasterProfile::create([
                'user_id' => $user->id,
                'display_name' => $pending['name'],
                'phone' => '+'.$pending['phone'],
                'slug' => $slug,
            ]);
            return $user;
        });
        $request->session()->forget(['pending_otp', 'otp_sent_at']);
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->route('dashboard');
    }

    // Existing legacy email/password users and new phone/password users share one lockout policy.
    public function login(Request $request)
    {
        $data = $request->validate([
            'identifier' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'password' => ['required', 'string'],
        ]);
        $identifier = trim((string) ($data['identifier'] ?? $data['email'] ?? ''));
        if (!$identifier) {
            throw ValidationException::withMessages(['identifier' => 'Введите номер телефона или почту.']);
        }
        $column = str_contains($identifier, '@') ? 'email' : 'phone';
        if ($column === 'phone') {
            try { $identifier = $this->phone($identifier); }
            catch (ValidationException $e) { throw ValidationException::withMessages(['identifier' => 'Введите корректный номер телефона.']); }
        } else {
            $identifier = strtolower($identifier);
        }
        $outcome = DB::transaction(function () use ($column, $identifier, $data) {
            $user = User::where($column, $identifier)->lockForUpdate()->first();
            if (!$user) {
                // Prevent obvious timing distinction between existing/missing accounts.
                Hash::check($data['password'], '$2y$12$KbQiHKyt3oc0UghTmumD/eJbcTsVHBDrY5r65J5xt2.jV4PaLNIuu');
                return ['invalid', null];
            }
            if ($user->login_locked_until && $user->login_locked_until->isFuture()) return ['locked', null];
            if ($user->login_locked_until && $user->login_locked_until->isPast()) {
                $user->failed_login_attempts = 0;
                $user->login_locked_until = null;
            }
            if (!Hash::check($data['password'], $user->password)) {
                $attempts = $user->failed_login_attempts + 1;
                $user->forceFill([
                    'failed_login_attempts' => $attempts,
                    'login_locked_until' => $attempts >= 5 ? now()->addHour() : null,
                ])->save();
                return [$attempts >= 5 ? 'locked' : 'invalid', null];
            }
            $user->forceFill(['failed_login_attempts' => 0, 'login_locked_until' => null])->save();
            return ['success', $user];
        });
        if ($outcome[0] === 'locked') {
            throw ValidationException::withMessages(['identifier' => 'После 5 неверных попыток вход заблокирован на 1 час.']);
        }
        if ($outcome[0] !== 'success') {
            throw ValidationException::withMessages(['identifier' => 'Неверный номер, почта или пароль.']);
        }
        Auth::login($outcome[1]);
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
