<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Tenant::current();

        if (! $tenant) {
            return redirect(config('app.url'));
        }

        if ($tenant->status === 'suspended') {
            return response()->view('errors.tenant-suspended', ['tenant' => $tenant], 403);
        }

        if ($tenant->status === 'provisioning') {
            // Redirect to the provisioning status page on the main domain
            return redirect(config('app.url') . '/register/' . $tenant->id . '/provisioning');
        }

        if ($tenant->status === 'failed') {
            return response()->view('errors.tenant-failed', ['tenant' => $tenant], 500);
        }

        if ($tenant->status !== 'active') {
            return redirect(config('app.url'));
        }

        // Scope Spatie's permission cache key to this tenant so that
        // permission changes in one tenant never affect another tenant's cache.
        app(PermissionRegistrar::class)->cacheKey =
            'spatie.permission.cache.tenant.' . $tenant->id;

        // Trial expiry check — login/logout stay reachable. Users who can manage the
        // subscription are sent to the subscription page; everyone else (including guests)
        // gets the trial-expired page, so nobody is bounced into a 403 or a redirect loop.
        if ($tenant->trialExpired() && ! $tenant->activeSubscription) {
            if (! $request->routeIs('login') && ! $request->routeIs('logout')) {
                $user = $request->user();

                if ($user && $user->can('manage subscription')) {
                    if (! $request->routeIs('subscription.*')) {
                        return redirect()->route('subscription.index');
                    }
                } else {
                    return response()->view('errors.trial-expired', ['tenant' => $tenant], 402);
                }
            }
        }

        // Cross-tenant session protection
        if (Auth::check()) {
            $sessionTenantId = $request->session()->get('tenant_id');

            if ($sessionTenantId && (int) $sessionTenantId !== (int) $tenant->id) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login');
            }

            if (! $sessionTenantId) {
                $request->session()->put('tenant_id', $tenant->id);
            }
        }

        return $next($request);
    }
}
