<?php

use App\Models\Tenant;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Tenant-Scoped Broadcast Channels
|--------------------------------------------------------------------------
| Channel names include the tenant slug to prevent cross-tenant leakage.
| Format: tenant.{tenantSlug}.user.{id}
*/

Broadcast::channel('tenant.{tenantSlug}.user.{id}', function ($user, $tenantSlug, $id) {
    $tenant = Tenant::current();

    return $tenant
        && $tenant->slug === $tenantSlug
        && (int) $user->id === (int) $id;
});
