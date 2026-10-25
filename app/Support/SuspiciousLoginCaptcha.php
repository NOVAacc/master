<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class SuspiciousLoginCaptcha
{
    public static function siteKey(): ?string
    {
        return config('services.turnstile.site_key') && config('services.turnstile.secret_key')
            ? config('services.turnstile.site_key') : null;
    }

    private static function keys(Request $request, string $scope, string $identifier): array
    {
        $ip = (string) $request->ip();
        return [
            'login-risk:'.hash('sha256', $scope.'|'.$ip.'|'.mb_strtolower($identifier)),
            'login-ip-risk:'.hash('sha256', $scope.'|'.$ip),
        ];
    }

    public static function check(Request $request, string $scope, string $identifier): void
    {
        if (!self::siteKey()) return;
        [$identityKey, $ipKey] = self::keys($request, $scope, $identifier);
        if (RateLimiter::attempts($identityKey) < 2 && RateLimiter::attempts($ipKey) < 8) return;
        $token = (string) $request->input('cf-turnstile-response', '');
        if (!$token || strlen($token) > 2048) {
            throw ValidationException::withMessages(['captcha' => 'Подтвердите, что вы не робот.']);
        }
        try {
            $response = Http::asForm()->timeout(5)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => config('services.turnstile.secret_key'),
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);
            if ($response->successful() && $response->json('success') === true) return;
        } catch (\Throwable $e) {
            report($e);
        }
        throw ValidationException::withMessages(['captcha' => 'Проверка не пройдена. Повторите подтверждение.']);
    }

    public static function failure(Request $request, string $scope, string $identifier): void
    {
        foreach (self::keys($request, $scope, $identifier) as $key) RateLimiter::hit($key, 600);
    }

    public static function success(Request $request, string $scope, string $identifier): void
    {
        [$identityKey] = self::keys($request, $scope, $identifier);
        RateLimiter::clear($identityKey);
    }
}
