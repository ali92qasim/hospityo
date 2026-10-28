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
 * Cuts imaging routes over from the laboratory permissions to the dedicated
 * imaging permissions in every tenant. Idempotent: permissions a role already
 * holds are skipped.
 *
 * Mapping (verb for verb, view/create/edit only):
 *   {verb} investigations                          -> {verb} imaging studies
 *   {verb} investigation orders | {verb} lab orders -> {verb} imaging orders
 *
 * Role names never select targets: any role that holds a source permission is
 * granted the matching imaging permission, because those source permissions are
 * exactly what used to unlock the imaging routes.
 *
 * Safety report (both modes): roles (other than Super Admin by name) that hold
 * a radiology results permission but nothing that maps to imaging studies or
 * orders, and no imaging studies/orders permission. They keep their reports
 * access, get no studies/orders access automatically, and are listed so the
 * deploy operator can review them. Nothing is granted to those roles.
 *
 * With --dry-run nothing is written: permissions are not created, no role is
 * granted anything and the Spatie permission cache is left untouched.
 */
class BackfillImagingPermissions extends Command
{
    public const VERBS = ['view', 'create', 'edit'];

    /** @var array<string, list<string>> imaging permission subject => legacy source subjects */
    public const SUBJECT_MAP = [
        'imaging studies' => ['investigations'],
        'imaging orders' => ['investigation orders', 'lab orders'],
    ];

    /** Roles excluded from the report-only list by name (still never granted by it). */
    public const REPORT_EXCLUDED_ROLES = ['Super Admin'];

    /** @var list<string> */
    public const RADIOLOGY_RESULT_PERMISSIONS = [
        'view radiology results',
        'create radiology results',
        'edit radiology results',
    ];

    protected $signature = 'rbac:backfill-imaging-permissions {--dry-run : Report the roles that would be updated without writing anything}';

    protected $description = 'Grant imaging studies/orders permissions to roles holding the matching investigations / investigation orders / lab orders permissions in every tenant and report radiology-only roles';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (Tenant::checkCurrent()) {
            $tenant = Tenant::current();
            $this->info('Backfilling 1 tenant(s).');
            [$updated, $reportOnly] = $this->backfillWithCacheIsolation($tenant, $dryRun);
            $this->reportTenant($tenant, $updated, $dryRun);
            $this->reportRolesWithoutImagingAccess("Tenant {$tenant->slug}", $reportOnly);

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

            $this->reportRolesWithoutImagingAccess('Current connection', $this->rolesWithoutImagingAccess());

            return self::SUCCESS;
        }

        $this->info("Backfilling {$tenants->count()} tenant(s).");

        foreach ($tenants as $tenant) {
            $tenant->makeCurrent();

            try {
                [$updated, $reportOnly] = $this->backfillWithCacheIsolation($tenant, $dryRun);
                $this->reportTenant($tenant, $updated, $dryRun);
                $this->reportRolesWithoutImagingAccess("Tenant {$tenant->slug}", $reportOnly);
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
    private function reportRolesWithoutImagingAccess(string $scope, array $roleNames): void
    {
        foreach ($roleNames as $name) {
            $this->line("  [report only] {$scope}: role \"{$name}\" holds a radiology results permission but no imaging studies/orders access; not granted.");
        }
    }

    /**
     * Imaging permission => legacy permissions that map to it.
     *
     * @return array<string, list<string>>
     */
    private function permissionMap(): array
    {
        $map = [];

        foreach (self::SUBJECT_MAP as $imagingSubject => $sources) {
            foreach (self::VERBS as $verb) {
                $map["{$verb} {$imagingSubject}"] = array_map(
                    fn (string $source) => "{$verb} {$source}",
                    $sources,
                );
            }
        }

        return $map;
    }

    /** @return list<string> */
    private function imagingPermissions(): array
    {
        return array_keys($this->permissionMap());
    }

    /** @return list<string> */
    private function sourcePermissions(): array
    {
        return array_values(array_unique(array_merge(...array_values($this->permissionMap()))));
    }

    /**
     * Read-only safety report. Never writes.
     *
     * @return list<string>
     */
    private function rolesWithoutImagingAccess(): array
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', self::REPORT_EXCLUDED_ROLES)
            ->whereHas('permissions', fn ($query) => $query
                ->whereIn('name', self::RADIOLOGY_RESULT_PERMISSIONS)
                ->where('guard_name', 'web'))
            ->whereDoesntHave('permissions', fn ($query) => $query
                ->whereIn('name', array_merge($this->imagingPermissions(), $this->sourcePermissions()))
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
            return [$this->backfillCurrent(true), $this->rolesWithoutImagingAccess()];
        }

        $registrar = app(PermissionRegistrar::class);
        $registrar->cacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
        $registrar->forgetCachedPermissions();

        try {
            $updated = $this->backfillCurrent(false);

            return [$updated, $this->rolesWithoutImagingAccess()];
        } finally {
            $registrar->forgetCachedPermissions();
        }
    }

    /**
     * @return list<string> names of roles that received (or would receive) at least one permission
     */
    private function backfillCurrent(bool $dryRun): array
    {
        $map = $this->permissionMap();

        if (! $dryRun) {
            foreach (array_keys($map) as $permission) {
                Permission::findOrCreate($permission, 'web');
            }
        }

        $updated = [];

        $roles = Role::with('permissions')
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();

        foreach ($roles as $role) {
            $held = $role->permissions->pluck('name');

            $missing = [];
            foreach ($map as $imagingPermission => $sources) {
                if ($held->contains($imagingPermission)) {
                    continue;
                }

                if ($held->intersect($sources)->isNotEmpty()) {
                    $missing[] = $imagingPermission;
                }
            }

            if ($missing === []) {
                continue;
            }

            if (! $dryRun) {
                $role->givePermissionTo($missing);
            }

            $updated[] = $role->name;
        }

        return $updated;
    }
}
