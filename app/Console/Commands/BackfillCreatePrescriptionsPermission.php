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
 * Grants `create prescriptions` to the Doctor and Nurse roles in every tenant.
 * Idempotent: roles that already hold it are skipped.
 *
 * Role names select backfill targets only; access is always checked by permission.
 * The safety report never grants.
 *
 * Safety report (both modes): every other role that holds `edit visits` but not
 * `create prescriptions` is listed so the deploy operator can review who would
 * lose prescribing. It is a read-only query; nothing is granted to those roles.
 *
 * With --dry-run nothing is written: the permission is not created, no role is
 * granted anything and the Spatie permission cache is left untouched.
 */
class BackfillCreatePrescriptionsPermission extends Command
{
    public const PERMISSION_NAME = 'create prescriptions';

    public const TARGET_ROLES = ['Doctor', 'Nurse'];

    public const REPORT_PERMISSION_NAME = 'edit visits';

    protected $signature = 'rbac:backfill-create-prescriptions {--dry-run : Report the roles that would be updated without writing anything}';

    protected $description = 'Grant create prescriptions to the Doctor and Nurse roles in every tenant and report other roles that edit visits without it';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (Tenant::checkCurrent()) {
            $tenant = Tenant::current();
            $this->info('Backfilling 1 tenant(s).');
            [$updated, $reportOnly] = $this->backfillWithCacheIsolation($tenant, $dryRun);
            $this->reportTenant($tenant, $updated, $dryRun);
            $this->reportUnprescribingRoles("Tenant {$tenant->slug}", $reportOnly);

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

            $this->reportUnprescribingRoles('Current connection', $this->unprescribingRoles());

            return self::SUCCESS;
        }

        $this->info("Backfilling {$tenants->count()} tenant(s).");

        foreach ($tenants as $tenant) {
            $tenant->makeCurrent();

            try {
                [$updated, $reportOnly] = $this->backfillWithCacheIsolation($tenant, $dryRun);
                $this->reportTenant($tenant, $updated, $dryRun);
                $this->reportUnprescribingRoles("Tenant {$tenant->slug}", $reportOnly);
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
     * @param  list<string>  $roleNames
     */
    private function reportUnprescribingRoles(string $scope, array $roleNames): void
    {
        foreach ($roleNames as $name) {
            $this->line("  [report only] {$scope}: role \"{$name}\" holds edit visits but not create prescriptions; not granted.");
        }
    }

    /**
     * Read-only safety report: roles outside TARGET_ROLES that hold `edit visits`
     * but not `create prescriptions`. Never writes. When `create prescriptions`
     * does not exist yet every such role lacks it; when `edit visits` does not
     * exist the result is empty.
     *
     * @return list<string>
     */
    private function unprescribingRoles(): array
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', self::TARGET_ROLES)
            ->whereHas('permissions', fn ($query) => $query
                ->where('name', self::REPORT_PERMISSION_NAME)
                ->where('guard_name', 'web'))
            ->whereDoesntHave('permissions', fn ($query) => $query
                ->where('name', self::PERMISSION_NAME)
                ->where('guard_name', 'web'))
            ->orderBy('name')
            ->pluck('name')
            ->all();
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
     * @return array{0: list<string>, 1: list<string>} the updated target roles and the report-only roles
     */
    private function backfillWithCacheIsolation(Tenant $tenant, bool $dryRun): array
    {
        if ($dryRun) {
            // Reads go straight to the database; the cache is neither read nor written.
            return [$this->backfillCurrent(true), $this->unprescribingRoles()];
        }

        $registrar = app(PermissionRegistrar::class);
        $registrar->cacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
        $registrar->forgetCachedPermissions();

        try {
            $updated = $this->backfillCurrent(false);

            // Computed after the grants so the targets, which now hold it, never appear.
            return [$updated, $this->unprescribingRoles()];
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
