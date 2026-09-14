<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;

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
            $this->backfillCurrent();

            return self::SUCCESS;
        }

        $tenants = $this->tenantsToBackfill();

        if ($tenants->isEmpty()) {
            $this->backfillCurrent();

            return self::SUCCESS;
        }

        foreach ($tenants as $tenant) {
            $tenant->makeCurrent();
            $this->backfillCurrent();
            Tenant::forgetCurrent();
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
        } catch (QueryException) {
            return collect();
        }
    }

    private function backfillCurrent(): void
    {
        foreach (self::CHILD_NAMES as $name) {
            Permission::findOrCreate($name, 'web');
        }

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
        }
    }
}
