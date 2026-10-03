<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Closes the unauthenticated install wizard once the application is installed,
 * so its setup actions (env rewrite, migrate:fresh, admin creation) can never be replayed.
 */
class EnsureAppNotInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (is_file(storage_path('installed'))) {
            abort(404);
        }

        return $next($request);
    }
}
