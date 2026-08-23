<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Support\PermissionRegistry;
use Illuminate\Console\Command;

class MigrateCoarsePermissions extends Command
{
    protected $signature = 'tenants:migrate-coarse-permissions {--tenant= : Slug of a specific tenant to migrate (omit for all)}';

    protected $description = 'Map legacy coarse role permissions to granular equivalents on all (or one) tenant(s)';

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
            $this->info("Migrating coarse permissions for tenant: {$tenant->slug}");

            $tenant->makeCurrent();

            $registrar = app()[\Spatie\Permission\PermissionRegistrar::class];
            $registrar->cacheKey = 'spatie.permission.cache.tenant.' . $tenant->id;
            $registrar->forgetCachedPermissions();

            $added = $this->migrateCurrentTenant();

            $registrar->forgetCachedPermissions();

            $this->info("  ✓ Done — {$tenant->slug} ({$added} permission(s) added)");

            Tenant::forgetCurrent();
        }

        $this->info('All done.');

        return self::SUCCESS;
    }

    /**
     * Migrate coarse permissions for every role in the current tenant database.
     */
    public function migrateCurrentTenant(): int
    {
        $added = 0;

        foreach (Role::with('permissions')->get() as $role) {
            $assigned = $role->permissions->pluck('name');

            foreach ($this->coarsePermissionMap() as $coarse => $granularPermissions) {
                if (! $assigned->contains($coarse)) {
                    continue;
                }

                $missing = collect($granularPermissions)
                    ->reject(fn (string $permission) => $assigned->contains($permission))
                    ->values();

                if ($missing->isEmpty()) {
                    continue;
                }

                foreach ($missing as $permission) {
                    Permission::findOrCreate($permission, 'web');
                }

                $role->givePermissionTo(...$missing->all());
                $assigned = $assigned->merge($missing);
                $added += $missing->count();
            }
        }

        return $added;
    }

    /**
     * @return array<string, list<string>>
     */
    protected function coarsePermissionMap(): array
    {
        return [
            'view hr'              => $this->viewPermissionsForModule('hr', 'view hr'),
            'view accounting'      => $this->viewPermissionsForModule('accounting', 'view accounting'),
            'manage doctor shares' => $this->viewCreateEditPermissionsForModule('doctor-share'),
            'manage backup'        => $this->permissionsForModule('backup'),
            'manage settings'      => ['view settings', 'edit settings'],
            'view pharmacy'        => $this->viewPermissionsForModule('pharmacy', 'view pharmacy'),
        ];
    }

    /**
     * @return list<string>
     */
    protected function permissionsForModule(string $module): array
    {
        $groups = PermissionRegistry::grouped()[$module]['groups'] ?? [];

        $permissions = [];

        foreach ($groups as $groupPermissions) {
            foreach ($groupPermissions as $permission) {
                $permissions[] = $permission;
            }
        }

        return array_values(array_unique($permissions));
    }

    /**
     * @return list<string>
     */
    protected function viewPermissionsForModule(string $module, ?string $exclude = null): array
    {
        return array_values(array_filter(
            $this->permissionsForModule($module),
            fn (string $permission) => str_starts_with($permission, 'view ')
                && $permission !== $exclude
        ));
    }

    /**
     * @return list<string>
     */
    protected function viewCreateEditPermissionsForModule(string $module): array
    {
        return array_values(array_filter(
            $this->permissionsForModule($module),
            fn (string $permission) => preg_match('/^(view|create|edit) /', $permission) === 1
        ));
    }
}
