<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireAdministrator
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin || !hash_equals(hash('sha256', $admin->getAuthPassword()), (string) $request->session()->get('admin_password_stamp'))) {
            Auth::guard('admin')->logout();
            $request->session()->forget('admin_password_stamp');
            return redirect()->route('admin.login');
        }
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');
        return $response;
    }
}
