<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // Trial expiry check
        if ($tenant->trialExpired() && !$tenant->activeSubscription) {
            return response()->view('errors.trial-expired', ['tenant' => $tenant], 403);
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
