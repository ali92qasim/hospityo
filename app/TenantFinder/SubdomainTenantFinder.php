<?php

namespace App\TenantFinder;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

/**
 * Resolves tenant from subdomain only.
 *
 * Requests to the main domain (saasy.test) return null — no tenant.
 * Requests to a subdomain (acme.saasy.test) look up the tenant by domain.
 */
class SubdomainTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {
        $host = $request->getHost();
        $baseDomain = $this->getBaseDomain();

        // Main domain — no tenant (landlord context)
        if ($host === $baseDomain) {
            return null;
        }

        // Check if this is actually a subdomain of our base domain
        if (! str_ends_with($host, '.' . $baseDomain)) {
            return null;
        }

        return app(IsTenant::class)::whereDomain($host)->first();
    }

    protected function getBaseDomain(): string
    {
        return parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';
    }
}
