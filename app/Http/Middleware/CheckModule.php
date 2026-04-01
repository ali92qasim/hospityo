<?php

namespace App\Http\Middleware;

use App\Models\ModuleRegistry;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SaaS-level module gating middleware.
 *
 * Checks if the current tenant's plan includes the module
 * that the requested route belongs to. This is Layer 1 (SaaS).
 * Layer 2 (user permissions) is handled by Spatie Permission.
 *
 * Usage in routes:
 *   ->middleware('module:pharmacy')     // explicit module
 *   ->middleware('module')              // auto-detect from route name
 */
class CheckModule
{
    public function handle(Request $request, Closure $next, ?string $module = null): Response
    {
        try {
            $tenant = Tenant::current();

            if (! $tenant) {
                return $next($request);
            }

            // Determine which module to check
            $module = $module ?? $this->detectModule($request);

            if (! $module) {
                // Route doesn't belong to any gated module — allow
                return $next($request);
            }

            if (! $tenant->hasModule($module)) {
                abort(403, 'Your plan does not include the ' . ModuleRegistry::nameFor($module) . ' module. Please upgrade your plan.');
            }

        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            abort(403, 'Unable to verify module access. Please contact support.');
        }

        return $next($request);
    }

    /**
     * Auto-detect the module from the current route name.
     */
    protected function detectModule(Request $request): ?string
    {
        $routeName = $request->route()?->getName();

        if (! $routeName) {
            return null;
        }

        return ModuleRegistry::moduleForRoute($routeName);
    }
}
