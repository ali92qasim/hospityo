<?php

use App\Models\Tenant;

if (! function_exists('tenant_storage_path')) {
    /**
     * Get a tenant-scoped storage path prefix.
     * Returns e.g. "tenants/acme-clinic" when a tenant is active,
     * or an empty string on the landlord domain.
     */
    function tenant_storage_path(string $subpath = ''): string
    {
        $tenant = Tenant::current();

        if (! $tenant) {
            return $subpath;
        }

        $base = 'tenants/' . $tenant->slug;

        return $subpath ? $base . '/' . ltrim($subpath, '/') : $base;
    }
}
