<?php

namespace App\Http\Middleware;

use App\Support\SettingsAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSettingsSection
{
    public function handle(Request $request, Closure $next, string $sectionKey): Response
    {
        $user = $request->user();

        if (! $user || ! SettingsAccess::canAccessSection($user, $sectionKey, $request->method())) {
            abort(403);
        }

        return $next($request);
    }
}
