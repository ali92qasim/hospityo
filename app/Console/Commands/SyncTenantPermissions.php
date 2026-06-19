<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SyncTenantPermissions extends Command
{
    protected $signature   = 'tenants:sync-permissions {--tenant= : Slug of a specific tenant to sync (omit for all)}';
    protected $description = 'Re-run RolePermissionSeeder on all (or one) tenant(s) to sync permissions and roles';

    public function handle(): int
    {
        $slug    = $this->option('tenant');
        $tenants = $slug
            ? Tenant::where('slug', $slug)->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->error('No tenants found' . ($slug ? " matching slug '{$slug}'" : '') . '.');
            return self::FAILURE;
        }

        foreach ($tenants as $tenant) {
            $this->info("Syncing permissions for tenant: {$tenant->slug}");

            $tenant->makeCurrent();

            // Set the tenant-specific cache key BEFORE clearing — this matches
            // EnsureTenantActive middleware so we actually clear the right cache.
            $registrar = app()[\Spatie\Permission\PermissionRegistrar::class];
            $registrar->cacheKey = 'spatie.permission.cache.tenant.' . $tenant->id;
            $registrar->forgetCachedPermissions();

            // Fix any permissions/roles created via the UI with the wrong guard_name
            \Illuminate\Support\Facades\DB::connection('tenant')
                ->table('permissions')
                ->where('guard_name', '!=', 'web')
                ->update(['guard_name' => 'web']);

            \Illuminate\Support\Facades\DB::connection('tenant')
                ->table('roles')
                ->where('guard_name', '!=', 'web')
                ->update(['guard_name' => 'web']);

            Artisan::call('db:seed', [
                '--class'    => 'Database\\Seeders\\RolePermissionSeeder',
                '--database' => 'tenant',
                '--force'    => true,
            ]);

            // Clear again after seeder so next web request rebuilds with new permissions
            $registrar->forgetCachedPermissions();

            $this->info("  ✓ Done — {$tenant->slug}");

            Tenant::forgetCurrent();
        }

        $this->info('All done.');
        return self::SUCCESS;
    }
}
