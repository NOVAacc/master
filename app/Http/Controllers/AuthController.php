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
        if (strlen($digits) === 9 && str_starts_with($digits, '0')) $digits = '996'.substr($digits, 1);
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
        $login = config('services.nikita.login');
        $password = config('services.nikita.password');
        $sender = config('services.nikita.sender');
        if (!$login || !$password || !$sender) {
            throw ValidationException::withMessages(['phone' => 'Отправка SMS пока не настроена.']);
        }
        if ($request->session()->get('otp_sent_at', 0) > time() - 60) {
            throw ValidationException::withMessages(['phone' => 'Повторная отправка доступна через минуту.']);
        }
        $code = (string) random_int(100000, 999999);
        $transaction = strtoupper(Str::random(12));
        $xmlValue = static fn (string $value): string => htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<message><login>'.$xmlValue($login).'</login><pwd>'.$xmlValue($password).'</pwd>'
            .'<id>'.$transaction.'</id><sender>'.$xmlValue($sender).'</sender>'
            .'<text>'.$xmlValue('BOSH: код входа '.$code.'. Действует 5 минут. Никому не сообщайте код.').'</text>'
            .'<phones><phone>'.$phone.'</phone></phones></message>';
        try {
            $response = Http::withBody($xml, 'application/xml')->accept('application/xml')
                ->timeout(10)->post('https://smspro.nikita.kg/api/message');
            $parsed = $response->successful()
                ? simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NONET)
                : false;
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['phone' => 'Сервис SMS временно недоступен.']);
        }
        if ($parsed === false || (string) $parsed->status !== '0' || (string) $parsed->id !== $transaction) {
            throw ValidationException::withMessages(['phone' => 'Не удалось отправить SMS. Проверьте номер, имя отправителя и настройки SMS.']);
        }
        $request->session()->put('pending_otp', [
            'digest' => hash_hmac('sha256', $code, (string) config('app.key')),
            'phone' => $phone,
            'name' => trim($data['name'] ?? ''),
            'mode' => $data['mode'],
            'expires_at' => time() + 300,
            'attempts' => 0,
        ]);
        $request->session()->put('otp_sent_at', time());
        return back()->with('otp_sent', true);
    }

    public function verifyCode(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'regex:/^[0-9]{6}$/']]);
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
        if (!hash_equals($pending['digest'], hash_hmac('sha256', $data['code'], (string) config('app.key')))) {
            throw ValidationException::withMessages(['code' => 'Неверный или просроченный код.']);
        }
        $user = DB::transaction(function () use ($pending) {
            $user = User::where('phone', $pending['phone'])->lockForUpdate()->first();
            if ($user) return $user;
            if ($pending['mode'] !== 'register') {
                throw ValidationException::withMessages(['code' => 'Аккаунт не найден. Сначала зарегистрируйтесь.']);
            }
            $user = User::create([
                'name' => $pending['name'],
                'phone' => $pending['phone'],
                'phone_verified_at' => now(),
                'password' => Hash::make(Str::random(48)),
            ]);
            $base = strtolower(Str::random(10));
            MasterProfile::create([
                'user_id' => $user->id,
                'display_name' => $pending['name'],
                'phone' => '+'.$pending['phone'],
                'slug' => 'master-'.$user->id.'-'.$base,
            ]);
            return $user;
        });
        $request->session()->forget(['pending_otp', 'otp_sent_at']);
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->route('dashboard');
    }

    // Existing email/password accounts remain accessible via the legacy sign-in form.
    public function login(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (!Auth::attempt($data)) throw ValidationException::withMessages(['email' => 'Неверный email или пароль.']);
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
