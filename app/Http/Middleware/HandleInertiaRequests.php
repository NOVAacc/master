<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [...parent::share($request),
            'seo' => \App\Services\Seo::forRequest($request),
            'site' => ['instagramUsername' => \App\Models\SiteSetting::instagramUsername()],
            'auth' => ['user' => $request->user()?->only('id', 'name', 'email')],
            'flash' => ['success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error')],
        ];
    }
}
