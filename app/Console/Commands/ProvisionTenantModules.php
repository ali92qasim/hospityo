<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantModuleProvisioner;
use Illuminate\Console\Command;

class ProvisionTenantModules extends Command
{
    protected $signature = 'tenants:provision-modules
        {--tenant= : Tenant slug (required)}
        {--grant : Apply grants (omit for dry run)}';

    protected $description = 'Idempotently grant plan-module Spatie permissions for one tenant';

    public function handle(TenantModuleProvisioner $provisioner): int
    {
        $slug = $this->option('tenant');

        if (! is_string($slug) || $slug === '') {
            $this->error('The --tenant option is required.');

            return self::FAILURE;
        }

        $tenant = Tenant::where('slug', $slug)->first();

        if (! $tenant) {
            $this->error("No tenant found matching slug '{$slug}'.");

            return self::FAILURE;
        }

        $modules = $tenant->plan?->modules ?? [];
        $dryRun = ! $this->option('grant');

        $result = $provisioner->grant($tenant, $modules, dryRun: $dryRun);

        $mode = $dryRun ? 'dry-run' : 'applied';
        $this->info("[{$mode}] {$tenant->slug}: seeded_empty=".($result->seededEmptyCatalog ? 'yes' : 'no')
            ." permissions_created={$result->permissionsCreated} grants_added={$result->grantsAdded}");

        return self::SUCCESS;
    }
}
