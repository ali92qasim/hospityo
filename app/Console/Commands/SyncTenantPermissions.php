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

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            Artisan::call('db:seed', [
                '--class'    => 'Database\\Seeders\\RolePermissionSeeder',
                '--database' => 'tenant',
                '--force'    => true,
            ]);

            $this->info("  ✓ Done — {$tenant->slug}");

            Tenant::forgetCurrent();
        }

        $this->info('All done.');
        return self::SUCCESS;
    }
}
