<?php

namespace App\Services;

use App\Models\ModuleRegistry;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Support\PermissionRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\PermissionRegistrar;

class GrantResult
{
    public function __construct(
        public bool $seededEmptyCatalog,
        public int $permissionsCreated,
        public int $grantsAdded,
    ) {}
}

class TenantModuleProvisioner
{
    public function needsAutoGrant(Tenant $tenant): bool
    {
        return $this->onTenant($tenant, fn () => Role::count() === 0);
    }

    /**
     * @param  list<string>  $modules
     */
    public function grant(Tenant $tenant, array $modules, bool $dryRun = false, bool $keepCurrent = false): GrantResult
    {
        return $this->onTenant($tenant, function () use ($modules, $dryRun) {
            $seeded = false;
            $created = 0;
            $granted = 0;

            if (Role::count() === 0) {
                if ($dryRun) {
                    return new GrantResult(true, 0, 0);
                }

                Artisan::call('db:seed', [
                    '--class' => RolePermissionSeeder::class,
                    '--database' => 'tenant',
                    '--force' => true,
                ]);
                $seeded = true;
            }

            $modules = ModuleRegistry::normalize($modules);

            foreach (RolePermissionSeeder::catalogPermissions() as $name) {
                if (! Permission::where('name', $name)->where('guard_name', 'web')->exists()) {
                    $created++;
                    if (! $dryRun) {
                        Permission::findOrCreate($name, 'web');
                    }
                }
            }

            if (! $dryRun) {
                Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
                foreach (array_keys(RolePermissionSeeder::defaultRolePermissions()) as $roleName) {
                    Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
                }
            }

            $superAdmin = Role::where('name', 'Super Admin')->first();
            $roleLists = RolePermissionSeeder::defaultRolePermissions();

            foreach ($modules as $module) {
                foreach (PermissionRegistry::forModule($module) as $name) {
                    if (! Permission::where('name', $name)->where('guard_name', 'web')->exists()) {
                        $created++;
                        if (! $dryRun) {
                            Permission::findOrCreate($name, 'web');
                        }
                    }

                    if ($dryRun && ! Permission::where('name', $name)->where('guard_name', 'web')->exists()) {
                        continue;
                    }

                    if ($superAdmin && ! $this->roleHas($superAdmin, $name)) {
                        $granted++;
                        if (! $dryRun) {
                            $superAdmin->givePermissionTo($name);
                            $superAdmin->unsetRelation('permissions');
                        }
                    }

                    $parentSlug = ModuleRegistry::parentOf($module);
                    $explicitGrantChild = $parentSlug !== null
                        && ! empty(ModuleRegistry::definitions()[$parentSlug]['child_access_requires_explicit_grant']);

                    if ($explicitGrantChild) {
                        $hospitalAdmin = Role::where('name', 'Hospital Administrator')->first();
                        if ($hospitalAdmin && ! $this->roleHas($hospitalAdmin, $name)) {
                            $granted++;
                            if (! $dryRun) {
                                $hospitalAdmin->givePermissionTo($name);
                                $hospitalAdmin->unsetRelation('permissions');
                            }
                        }
                    }

                    foreach ($roleLists as $roleName => $allowed) {
                        if (! in_array($name, $allowed, true)) {
                            continue;
                        }

                        $role = Role::where('name', $roleName)->first();
                        if (! $role || $this->roleHas($role, $name)) {
                            continue;
                        }

                        $granted++;
                        if (! $dryRun) {
                            $role->givePermissionTo($name);
                            $role->unsetRelation('permissions');
                        }
                    }
                }
            }

            return new GrantResult($seeded, $created, $granted);
        }, $keepCurrent);
    }

    private function roleHas(Role $role, string $name): bool
    {
        $role->loadMissing('permissions');

        return $role->permissions->contains('name', $name);
    }

    private function onTenant(Tenant $tenant, callable $callback, bool $keepCurrent = false): mixed
    {
        $tenant->makeCurrent();

        $registrar = app(PermissionRegistrar::class);
        $registrar->cacheKey = 'spatie.permission.cache.tenant.'.$tenant->id;
        $registrar->forgetCachedPermissions();

        try {
            return $callback();
        } finally {
            $registrar->forgetCachedPermissions();
            if (! $keepCurrent) {
                Tenant::forgetCurrent();
            }
        }
    }
}
