<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RestrictUploads;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [SecurityHeaders::class, RestrictUploads::class, HandleInertiaRequests::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Recover safely from stale CSRF tokens after an idle tab or expired session.
        // Never replay a mutation without a fresh authenticated request and token.
        $exceptions->respond(function ($response, $exception, $request) {
            if ($response->getStatusCode() === 419 && ! $request->expectsJson()) {
                return redirect()->route($request->user() ? 'dashboard' : 'login')
                    ->with('error', 'Сессия устарела. Страница обновлена — повторите действие.');
            }
            return $response;
        });
    })->create();
