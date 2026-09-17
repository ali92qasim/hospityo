<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;

class BackfillManagePacPermission extends Command
{
    public const SURGERIES_NAMES = [
        'view surgeries',
        'create surgeries',
        'edit surgeries',
        'delete surgeries',
    ];

    public const PAC_NAME = 'manage pac';

    protected $signature = 'ot:backfill-pac-permission';

    protected $description = 'Grant manage pac to roles that already hold surgeries CRUD access';

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
        Permission::findOrCreate(self::PAC_NAME, 'web');

        $updated = 0;

        foreach (Role::with('permissions')->get() as $role) {
            $hasSurgeries = false;
            foreach (self::SURGERIES_NAMES as $name) {
                if ($role->permissions->contains('name', $name)) {
                    $hasSurgeries = true;
                    break;
                }
            }
            if (! $hasSurgeries) {
                continue;
            }
            $role->givePermissionTo(self::PAC_NAME);
            $updated++;
        }

        return $updated;
    }
}
