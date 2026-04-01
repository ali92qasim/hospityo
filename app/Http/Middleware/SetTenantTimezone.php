<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantTimezone
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Tenant::checkCurrent()) {
            try {
                $timezone = cache('settings.timezone', config('app.timezone', 'Asia/Karachi'));
                config(['app.timezone' => $timezone]);
                date_default_timezone_set($timezone);
            } catch (\Exception $e) {
                // Skip if cache not ready
            }
        }

        return $next($request);
    }
}
