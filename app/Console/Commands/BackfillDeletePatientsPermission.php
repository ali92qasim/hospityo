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
 * Grants `delete patients` to the Hospital Administrator role in every tenant.
 * Idempotent: roles that already hold it are skipped.
 *
 * Role names select backfill targets only; access is always checked by permission.
 * The safety report never grants. `restore patients` is never granted here.
 *
 * Safety report (both modes): every other role that holds any patient permission
 * but not `delete patients` (excluding Super Admin and Hospital Administrator by
 * name) is listed so the deploy operator can review it. Nothing is granted to
 * those roles.
 *
 * With --dry-run nothing is written: the permission is not created, no role is
 * granted anything and the Spatie permission cache is left untouched.
 */
class BackfillDeletePatientsPermission extends Command
{
    public const PERMISSION_NAME = 'delete patients';

    public const TARGET_ROLES = ['Hospital Administrator'];

    /** Roles excluded from the report-only list by name (still never granted). */
    public const REPORT_EXCLUDED_ROLES = ['Super Admin', 'Hospital Administrator'];

    /** @var list<string> */
    public const PATIENT_PERMISSIONS = [
        'view patients',
        'create patients',
        'edit patients',
        'delete patients',
        'restore patients',
    ];

    protected $signature = 'rbac:backfill-delete-patients {--dry-run : Report the roles that would be updated without writing anything}';

    protected $description = 'Grant delete patients to the Hospital Administrator role in every tenant and report other roles that hold patient permissions without delete patients';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (Tenant::checkCurrent()) {
            $tenant = Tenant::current();
            $this->info('Backfilling 1 tenant(s).');
            [$updated, $reportOnly] = $this->backfillWithCacheIsolation($tenant, $dryRun);
            $this->reportTenant($tenant, $updated, $dryRun);
            $this->reportRolesWithoutDelete("Tenant {$tenant->slug}", $reportOnly);

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

            $this->reportRolesWithoutDelete('Current connection', $this->rolesWithoutDeletePatients());

            return self::SUCCESS;
        }

        $this->info("Backfilling {$tenants->count()} tenant(s).");

        foreach ($tenants as $tenant) {
            $tenant->makeCurrent();

            try {
                [$updated, $reportOnly] = $this->backfillWithCacheIsolation($tenant, $dryRun);
                $this->reportTenant($tenant, $updated, $dryRun);
                $this->reportRolesWithoutDelete("Tenant {$tenant->slug}", $reportOnly);
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
    private function reportRolesWithoutDelete(string $scope, array $roleNames): void
    {
        foreach ($roleNames as $name) {
            $this->line("  [report only] {$scope}: role \"{$name}\" holds a patient permission but not delete patients; not granted.");
        }
    }

    /**
     * Read-only safety report. Never writes.
     *
     * @return list<string>
     */
    private function rolesWithoutDeletePatients(): array
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', self::REPORT_EXCLUDED_ROLES)
            ->whereHas('permissions', fn ($query) => $query
                ->whereIn('name', self::PATIENT_PERMISSIONS)
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
     * @return array{0: list<string>, 1: list<string>}
     */
    private function backfillWithCacheIsolation(Tenant $tenant, bool $dryRun): array
    {
        if ($dryRun) {
            return [$this->backfillCurrent(true), $this->rolesWithoutDeletePatients()];
        }

        $registrar = app(PermissionRegistrar::class);
        $registrar->cacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
        $registrar->forgetCachedPermissions();

        try {
            $updated = $this->backfillCurrent(false);

            return [$updated, $this->rolesWithoutDeletePatients()];
        } finally {
            $registrar->forgetCachedPermissions();
        }
    }

    /**
     * @return list<string>
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
