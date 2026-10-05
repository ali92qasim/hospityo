<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;

/**
 * Grants `manage subscription` to the Super Admin and Hospital Administrator
 * roles in every tenant. Idempotent: roles that already hold it are skipped.
 *
 * Role names select backfill targets only; access is always checked by permission.
 *
 * With --dry-run nothing is written: the permission is not created, no role is
 * granted anything and the Spatie permission cache is left untouched.
 */
class BackfillManageSubscriptionPermission extends Command
{
    public const PERMISSION_NAME = 'manage subscription';

    public const TARGET_ROLES = ['Super Admin', 'Hospital Administrator'];

    protected $signature = 'rbac:backfill-manage-subscription {--dry-run : Report the roles that would be updated without writing anything}';

    protected $description = 'Grant manage subscription to the Super Admin and Hospital Administrator roles in every tenant';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (Tenant::checkCurrent()) {
            $tenant = Tenant::current();
            $this->info('Backfilling 1 tenant(s).');
            $updated = $this->backfillWithCacheIsolation($tenant, $dryRun);
            $this->reportTenant($tenant, $updated, $dryRun);

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

            $updated = $this->backfillCurrent($dryRun);

            if ($dryRun) {
                $this->info('[dry-run] Current connection: would update '.count($updated).' role(s) ('.implode(', ', $updated).').');
            } else {
                $this->info('Backfilled current connection ('.count($updated).' role(s) updated).');
            }

            return self::SUCCESS;
        }

        $this->info("Backfilling {$tenants->count()} tenant(s).");

        foreach ($tenants as $tenant) {
            $tenant->makeCurrent();

            try {
                $updated = $this->backfillWithCacheIsolation($tenant, $dryRun);
                $this->reportTenant($tenant, $updated, $dryRun);
            } finally {
                Tenant::forgetCurrent();
            }
        }

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $updated
     */
    private function reportTenant(Tenant $tenant, array $updated, bool $dryRun): void
    {
        if ($dryRun) {
            $this->info("[dry-run] Tenant {$tenant->slug}: would update ".count($updated).' role(s) ('.implode(', ', $updated).').');

            return;
        }

        $this->info("Backfilled tenant {$tenant->slug} (".count($updated).' role(s) updated).');
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

    /**
     * @return list<string>
     */
    private function backfillWithCacheIsolation(Tenant $tenant, bool $dryRun): array
    {
        if ($dryRun) {
            // Reads go straight to the database; the cache is neither read nor written.
            return $this->backfillCurrent(true);
        }

        $registrar = app(PermissionRegistrar::class);
        $registrar->cacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
        $registrar->forgetCachedPermissions();

        try {
            return $this->backfillCurrent(false);
        } finally {
            $registrar->forgetCachedPermissions();
        }
    }

    /**
     * @return list<string> names of the target roles updated (or that would be, on dry run)
     */
    private function backfillCurrent(bool $dryRun): array
    {
        if ($dryRun) {
            $permissionExists = Permission::query()
                ->where('name', self::PERMISSION_NAME)
                ->where('guard_name', 'web')
                ->exists();
        } else {
            Permission::findOrCreate(self::PERMISSION_NAME, 'web');
            $permissionExists = true;
        }

        $roles = Role::with('permissions')
            ->whereIn('name', self::TARGET_ROLES)
            ->where('guard_name', 'web')
            ->get()
            ->keyBy('name');

        $updated = [];

        foreach (self::TARGET_ROLES as $roleName) {
            $role = $roles->get($roleName);
            if ($role === null) {
                continue;
            }

            // When the permission does not exist yet (dry run), every target role lacks it.
            if ($permissionExists && $role->permissions->contains('name', self::PERMISSION_NAME)) {
                continue;
            }

            if (! $dryRun) {
                $role->givePermissionTo(self::PERMISSION_NAME);
            }

            $updated[] = $role->name;
        }

        return $updated;
    }
}
