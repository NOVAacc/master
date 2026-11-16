<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // The registration completion request is bound to an OAuth session.
        // IP-only throttling groups many mobile users behind one NAT/proxy.
        RateLimiter::for('google-signup', function (Request $request) {
            $session = $request->session();
            $identity = (string) ($session->get('google_signup.sub') ?? '');
            $key = $identity !== '' ? hash('sha256', $identity) : $session->getId();

            return Limit::perMinutes(10, 12)->by('google-signup:'.$key);
        });
    }
}
