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

class GoogleAuthController extends Controller
{
    public function redirect(Request $request)
    {
        abort_unless(config('services.google.client_id') && config('services.google.client_secret'), 503);
        $state = Str::random(64);
        $request->session()->put('google_oauth', [
            'state' => $state,
            'mode' => Auth::check() ? 'link' : 'login',
            'user_id' => Auth::id(),
            'created_at' => time(),
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ], '', '&', PHP_QUERY_RFC3986));
    }

    public function callback(Request $request)
    {
        $pending = $request->session()->pull('google_oauth');
        if (!is_array($pending) || time() - ($pending['created_at'] ?? 0) > 600
            || !is_string($request->query('state'))
            || !hash_equals((string) ($pending['state'] ?? ''), $request->query('state'))
            || !$request->filled('code') || $request->filled('error')) {
            return redirect()->route('login')->with('error', 'Вход через Google отменён или срок запроса истёк. Попробуйте ещё раз.');
        }

        try {
            $tokens = Http::asForm()->timeout(12)->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'code' => $request->query('code'),
                'grant_type' => 'authorization_code',
                'redirect_uri' => config('services.google.redirect'),
            ])->throw()->json();

            if (!is_string($tokens['access_token'] ?? null)) {
                throw new \RuntimeException('Missing access token');
            }
            $identity = Http::withToken($tokens['access_token'])->acceptJson()
                ->timeout(12)->get('https://openidconnect.googleapis.com/v1/userinfo')->throw()->json();
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('login')->with('error', 'Не удалось проверить Google-аккаунт. Попробуйте снова.');
        }

        $sub = $identity['sub'] ?? null;
        $email = strtolower(trim((string) ($identity['email'] ?? '')));
        if (!is_string($sub) || $sub === '' || strlen($sub) > 255 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255 ||
            ($identity['email_verified'] ?? false) !== true) {
            return redirect()->route('login')->with('error', 'Google не подтвердил адрес электронной почты.');
        }

        if (($pending['mode'] ?? '') === 'link') {
            $user = $request->user();
            if (!$user || (int) $user->id !== (int) ($pending['user_id'] ?? 0)
                || strcasecmp($user->email, $email) !== 0) {
                abort(403);
            }
            if (User::where('google_sub', $sub)->where('id', '!=', $user->id)->exists()
                || ($user->google_sub && $user->google_sub !== $sub)) {
                return redirect()->route('dashboard')->with('error', 'Этот Google-аккаунт уже привязан к другому профилю.');
            }
            $user->forceFill(['google_sub' => $sub, 'email_verified_at' => $user->email_verified_at ?: now()])->save();
            return redirect()->route('dashboard')->with('success', 'Google-аккаунт подключён.');
        }

        $user = User::where('google_sub', $sub)->first();
        if ($user) {
            if ($user->login_locked_until && $user->login_locked_until->isFuture()) {
                return redirect()->route('login')->with('error', 'Доступ к аккаунту временно заблокирован.');
            }
            Auth::login($user);
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        if (User::where('email', $email)->exists()) {
            return redirect()->route('login')->with('error', 'Аккаунт с этой почтой уже существует. Войдите по паролю, затем привяжите Google в кабинете.');
        }

        $request->session()->put('google_signup', [
            'sub' => $sub,
            'email' => $email,
            'name' => mb_substr(trim((string) ($identity['name'] ?? '')), 0, 100) ?: 'Мастер',
            'created_at' => time(),
        ]);
        return redirect()->route('google.finish');
    }

    public function finishPage(Request $request)
    {
        $pending = $request->session()->get('google_signup');
        if (!is_array($pending) || time() - ($pending['created_at'] ?? 0) > 1800) {
            return redirect()->route('register')->with('error', 'Срок регистрации через Google истёк. Попробуйте снова.');
        }
        return Inertia::render('GoogleFinish', ['email' => $pending['email'], 'name' => $pending['name']]);
    }

    public function finish(Request $request)
    {
        $pending = $request->session()->get('google_signup');
        if (!is_array($pending) || time() - ($pending['created_at'] ?? 0) > 1800) {
            throw ValidationException::withMessages(['phone' => 'Срок регистрации истёк. Начните вход через Google заново.']);
        }
        $data = $request->validate(['phone' => ['required', 'string', 'max:32']]);
        $digits = preg_replace('/\D+/', '', $data['phone']);
        if (strlen($digits) === 10 && str_starts_with($digits, '0')) $digits = '996'.substr($digits, 1);
        if (strlen($digits) === 9) $digits = '996'.$digits;
        if (!preg_match('/^996\d{9}$/', $digits)) {
            throw ValidationException::withMessages(['phone' => 'Укажите номер Кыргызстана в формате 996XXXXXXXXX.']);
        }
        try {
            $user = DB::transaction(function () use ($pending, $digits) {
                if (User::where('email', $pending['email'])->exists() || User::where('google_sub', $pending['sub'])->exists()) {
                    throw ValidationException::withMessages(['phone' => 'Аккаунт уже существует. Вернитесь на страницу входа.']);
                }
                if (User::where('phone', $digits)->exists()) {
                    throw ValidationException::withMessages(['phone' => 'Этот номер телефона уже зарегистрирован.']);
                }
                $user = User::create([
                    'name' => $pending['name'], 'email' => $pending['email'], 'phone' => $digits,
                    'password' => Hash::make(Str::random(64)),
                ]);
                $user->forceFill(['google_sub' => $pending['sub'], 'email_verified_at' => now()])->save();
                do { $slug = strtolower(Str::random(7)); }
                while (MasterProfile::where('slug', $slug)->exists());
                MasterProfile::create([
                    'user_id' => $user->id, 'display_name' => $pending['name'],
                    'phone' => $digits, 'slug' => $slug, 'onboarding_industry_done' => false,
                ]);
                return $user;
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if (in_array((string) $e->getCode(), ['23000', '23505'], true)) {
                throw ValidationException::withMessages(['phone' => 'Телефон или почта уже заняты.']);
            }
            throw $e;
        }
        $request->session()->forget('google_signup');
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->route('dashboard');
    }
}
