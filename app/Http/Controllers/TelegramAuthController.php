<?php

namespace App\Http\Controllers;

use App\Mail\RegistrationPassword;
use App\Models\MasterProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TelegramAuthController extends Controller
{
    public function redirect(Request $request)
    {
        abort_unless(config('services.telegram.client_id') && config('services.telegram.client_secret'), 503);
        $state = Str::random(64);
        $verifier = Str::random(96);
        $request->session()->put('telegram_oidc', [
            'state' => $state, 'verifier' => $verifier,
            'mode' => Auth::check() ? 'link' : 'login',
            'user_id' => Auth::id(), 'created_at' => time(),
        ]);
        return redirect()->away('https://oauth.telegram.org/auth?'.http_build_query([
            'client_id' => config('services.telegram.client_id'),
            'redirect_uri' => route('telegram.auth.callback'),
            'response_type' => 'code',
            'scope' => 'openid profile',
            'state' => $state,
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986));
    }

    public function callback(Request $request)
    {
        $pending = $request->session()->pull('telegram_oidc');
        if (!is_array($pending) || time() - ($pending['created_at'] ?? 0) > 600
            || !is_string($request->query('state'))
            || !hash_equals((string) ($pending['state'] ?? ''), $request->query('state'))
            || !$request->filled('code') || $request->filled('error')) {
            return redirect()->route('login')->with('error', 'Вход через Telegram отменён или истёк. Повторите попытку.');
        }
        try {
            $tokens = Http::asForm()->withBasicAuth(
                (string) config('services.telegram.client_id'),
                (string) config('services.telegram.client_secret')
            )->timeout(12)->post('https://oauth.telegram.org/token', [
                'grant_type' => 'authorization_code',
                'code' => $request->query('code'),
                'redirect_uri' => route('telegram.auth.callback'),
                'client_id' => config('services.telegram.client_id'),
                'code_verifier' => $pending['verifier'],
            ])->throw()->json();
            $identity = $this->verifyIdToken($tokens['id_token'] ?? '');
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('login')->with('error', 'Не удалось подтвердить Telegram. Попробуйте снова.');
        }

        $sub = (string) $identity['sub'];
        if (($pending['mode'] ?? '') === 'link') {
            $user = $request->user();
            abort_unless($user && $user->id === (int) ($pending['user_id'] ?? 0), 403);
            if (User::where('telegram_sub', $sub)->where('id', '!=', $user->id)->exists()
                || ($user->telegram_sub && $user->telegram_sub !== $sub)) {
                return redirect()->route('dashboard')->with('error', 'Telegram уже привязан к другому кабинету.');
            }
            $user->forceFill(['telegram_sub' => $sub])->save();
            return redirect()->route('dashboard')->with('success', 'Telegram подключён для входа.');
        }

        $user = User::where('telegram_sub', $sub)->first();
        if ($user) {
            if ($user->login_locked_until && $user->login_locked_until->isFuture()) {
                return redirect()->route('login')->with('error', 'Доступ к аккаунту временно заблокирован.');
            }
            Auth::login($user);
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        $request->session()->put('telegram_signup', [
            'sub' => $sub,
            'name' => mb_substr(trim((string) ($identity['name'] ?? '')), 0, 100) ?: 'Мастер',
            'created_at' => time(),
        ]);
        return redirect()->route('telegram.auth.finish');
    }

    public function finishPage(Request $request)
    {
        $pending = $request->session()->get('telegram_signup');
        if (!is_array($pending) || time() - ($pending['created_at'] ?? 0) > 1800) {
            return redirect()->route('login')->with('error', 'Срок регистрации истёк. Войдите через Telegram повторно.');
        }
        return Inertia::render('TelegramFinish', ['name' => $pending['name']]);
    }

    public function finish(Request $request)
    {
        $pending = $request->session()->get('telegram_signup');
        if (!is_array($pending) || time() - ($pending['created_at'] ?? 0) > 1800) {
            throw ValidationException::withMessages(['email' => 'Повторите вход через Telegram.']);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
        ]);
        $email = strtolower(trim($data['email']));
        $digits = preg_replace('/\D+/', '', $data['phone']);
        if (strlen($digits) === 10 && str_starts_with($digits, '0')) $digits = '996'.substr($digits, 1);
        if (strlen($digits) === 9) $digits = '996'.$digits;
        if (!preg_match('/^996\d{9}$/', $digits)) {
            throw ValidationException::withMessages(['phone' => 'Укажите номер в формате 996XXXXXXXXX.']);
        }
        if (User::where('email', $email)->orWhere('phone', $digits)->orWhere('telegram_sub', $pending['sub'])->exists()) {
            throw ValidationException::withMessages(['email' => 'Аккаунт уже существует. Войдите обычным способом и привяжите Telegram в кабинете.']);
        }
        try {
            DB::transaction(function () use ($data, $email, $digits, $pending) {
                $user = User::create([
                    'name' => trim($data['name']), 'email' => $email, 'phone' => $digits,
                    'password' => Hash::make(Str::random(64)),
                ]);
                $user->forceFill(['telegram_sub' => $pending['sub']])->save();
                do { $slug = strtolower(Str::random(7)); }
                while (MasterProfile::where('slug', $slug)->exists());
                MasterProfile::create([
                    'user_id' => $user->id, 'display_name' => $user->name,
                    'phone' => $digits, 'slug' => $slug, 'onboarding_industry_done' => false,
                ]);
                $verifyUrl = URL::temporarySignedRoute('security.email.verify', now()->addMinutes(60), [
                    'user' => $user->id, 'hash' => sha1($email),
                ]);
                Mail::to($email)->send(new RegistrationPassword($user->name, $verifyUrl));
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if (in_array((string) $e->getCode(), ['23000', '23505'], true)) {
                throw ValidationException::withMessages(['email' => 'Номер или почта уже заняты.']);
            }
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            throw ValidationException::withMessages(['email' => 'Не удалось отправить письмо. Проверьте почту и попробуйте позже.']);
        }
        $request->session()->forget('telegram_signup');
        return redirect()->route('login')->with('success', 'Кабинет создан. Подтвердите почту по ссылке из письма, затем войдите через Telegram.');
    }

    private function verifyIdToken(mixed $token): array
    {
        if (!is_string($token) || strlen($token) > 16384) throw new \RuntimeException('Invalid id token');
        $parts = explode('.', $token);
        if (count($parts) !== 3) throw new \RuntimeException('Invalid JWT');
        $decode = fn (string $part) => base64_decode(strtr($part, '-_', '+/'), true);
        $header = json_decode($decode($parts[0]) ?: '', true);
        $claims = json_decode($decode($parts[1]) ?: '', true);
        if (!is_array($header) || !is_array($claims) || ($header['alg'] ?? '') !== 'RS256'
            || !is_string($header['kid'] ?? null)) throw new \RuntimeException('Invalid JWT header');
        $keys = Cache::remember('telegram_oidc_jwks', 3600, fn () =>
            Http::timeout(10)->get('https://oauth.telegram.org/.well-known/jwks.json')->throw()->json('keys')
        );
        $jwk = collect($keys)->first(fn ($key) => is_array($key)
            && ($key['kid'] ?? '') === $header['kid'] && ($key['kty'] ?? '') === 'RSA'
            && (!isset($key['alg']) || $key['alg'] === 'RS256'));
        if (!$jwk || !isset($jwk['n'], $jwk['e'])) throw new \RuntimeException('Unknown Telegram key');
        $integer = function (string $value) use ($decode): string {
            $bytes = $decode($value);
            if ($bytes === false || $bytes === '') throw new \RuntimeException('Bad RSA parameter');
            if (ord($bytes[0]) >= 128) $bytes = "\x00".$bytes;
            return "\x02".$this->asnLength(strlen($bytes)).$bytes;
        };
        $rsa = $integer($jwk['n']).$integer($jwk['e']);
        $rsa = "\x30".$this->asnLength(strlen($rsa)).$rsa;
        $alg = hex2bin('300d06092a864886f70d0101010500');
        $bitString = "\x03".$this->asnLength(strlen($rsa) + 1)."\x00".$rsa;
        $spki = $alg.$bitString;
        $spki = "\x30".$this->asnLength(strlen($spki)).$spki;
        $pem = "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($spki), 64, "\n")."-----END PUBLIC KEY-----\n";
        if (openssl_verify($parts[0].'.'.$parts[1], $decode($parts[2]) ?: '', $pem, OPENSSL_ALGO_SHA256) !== 1) {
            throw new \RuntimeException('Invalid JWT signature');
        }
        $now = time();
        if (($claims['iss'] ?? '') !== 'https://oauth.telegram.org'
            || (string) ($claims['aud'] ?? '') !== (string) config('services.telegram.client_id')
            || !is_numeric($claims['exp'] ?? null) || (int) $claims['exp'] <= $now
            || !is_numeric($claims['iat'] ?? null) || (int) $claims['iat'] > $now + 60
            || !is_string($claims['sub'] ?? null) || !preg_match('/^\d{1,32}$/', $claims['sub'])) {
            throw new \RuntimeException('Invalid JWT claims');
        }
        return $claims;
    }

    private function asnLength(int $length): string
    {
        if ($length < 128) return chr($length);
        $bytes = ltrim(pack('N', $length), "\x00");
        return chr(0x80 | strlen($bytes)).$bytes;
    }
}
