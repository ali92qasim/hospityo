<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;

class BackfillSettingsSectionPermissions extends Command
{
    public const PARENT_NAMES = [
        'access settings',
        'manage settings',
        'view settings',
        'edit settings',
    ];

    public const CHILD_NAMES = [
        'access settings.hospital-info',
        'access settings.prescription-print',
    ];

    protected $signature = 'settings:backfill-section-permissions';

    protected $description = 'Grant settings child permissions to roles that already hold parent or legacy settings access';

    public function handle(): int
    {
        if (Tenant::checkCurrent()) {
            $tenant = Tenant::current();
            $this->info('Backfilling 1 tenant(s).');
            $updated = $this->backfillWithCacheIsolation($tenant);
            $this->info("Backfilled current tenant {$tenant->slug} ({$updated} role(s) updated).");

            return self::SUCCESS;
        }

        try {
            $tenants = $this->tenantsToBackfill();
        } catch (QueryException) {
            $this->error('Unable to list tenants from the landlord connection.');

            return self::FAILURE;
        }

        if ($tenants->isEmpty()) {
            if (! $this->allowsCurrentConnectionFallback()) {
                $this->error('No tenants found.');

                return self::FAILURE;
            }

            $updated = $this->backfillCurrent();
            $this->info("Backfilled current connection ({$updated} role(s) updated).");

            return self::SUCCESS;
        }

        $this->info("Backfilling {$tenants->count()} tenant(s).");

        foreach ($tenants as $tenant) {
            $tenant->makeCurrent();

            try {
                $updated = $this->backfillWithCacheIsolation($tenant);
                $this->info("Backfilled tenant {$tenant->slug} ({$updated} role(s) updated).");
            } finally {
                Tenant::forgetCurrent();
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, Tenant>
     */
    private function tenantsToBackfill(): Collection
    {
        try {
            return Tenant::all();
        } catch (QueryException $e) {
            if (! $this->allowsCurrentConnectionFallback()) {
                throw $e;
            }

            return collect();
        }
    }

    private function allowsCurrentConnectionFallback(): bool
    {
        return app()->environment('testing');
    }

    private function backfillWithCacheIsolation(Tenant $tenant): int
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->cacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
        $registrar->forgetCachedPermissions();

        try {
            return $this->backfillCurrent();
        } finally {
            $registrar->forgetCachedPermissions();
        }
    }

    private function backfillCurrent(): int
    {
        foreach (self::CHILD_NAMES as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $updated = 0;

        foreach (Role::with('permissions')->get() as $role) {
            $hasParent = false;
            foreach (self::PARENT_NAMES as $parent) {
                if ($role->permissions->contains('name', $parent)) {
                    $hasParent = true;
                    break;
                }
            }
            if (! $hasParent) {
                continue;
            }
            $role->givePermissionTo(self::CHILD_NAMES);
            $updated++;
        }

        return $updated;
    }
}
