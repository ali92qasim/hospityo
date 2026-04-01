<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('super_admin')->check()) {
            return redirect()->route('super-admin.login');
        }

        return $next($request);
    }
}
