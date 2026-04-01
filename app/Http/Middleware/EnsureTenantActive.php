<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures a valid, active tenant exists for the request.
 * Also prevents cross-tenant session abuse by validating
 * the session's tenant matches the current tenant.
 */
class EnsureTenantActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Tenant::current();

        if (! $tenant) {
            return redirect(config('app.url'));
        }

        if ($tenant->status === 'suspended') {
            abort(403, 'This hospital account has been suspended. Please contact support.');
        }

        if ($tenant->status !== 'active') {
            abort(503, 'This hospital account is being set up. Please try again shortly.');
        }

        // Cross-tenant session protection:
        // If user is authenticated but session was created for a different tenant, log them out
        if (Auth::check()) {
            $sessionTenantId = $request->session()->get('tenant_id');

            if ($sessionTenantId && (int) $sessionTenantId !== (int) $tenant->id) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login');
            }

            // Stamp the session with the current tenant ID
            if (! $sessionTenantId) {
                $request->session()->put('tenant_id', $tenant->id);
            }
        }

        return $next($request);
    }
}
