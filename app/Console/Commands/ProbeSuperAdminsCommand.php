<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;

/**
 * Read-only Super Admin coverage probe for RBAC Wave 2.
 *
 * Lists every user holding the Super Admin role per tenant and flags tenants
 * with none. Never grants roles or permissions.
 */
class ProbeSuperAdminsCommand extends Command
{
    public const ROLE_NAME = 'Super Admin';

    protected $signature = 'rbac:probe-super-admins';

    protected $description = 'List Super Admin users per tenant and flag tenants with none (read-only)';

    public function handle(): int
    {
        $flagged = 0;

        if (Tenant::checkCurrent()) {
            $tenant = Tenant::current();
            $this->info('Probing 1 tenant(s).');
            if ($this->probeCurrent("Tenant {$tenant->slug}")) {
                $flagged++;
            }

            return $this->finish($flagged);
        }

        try {
            $tenants = $this->tenantsToProbe();
        } catch (QueryException) {
            $this->error('Unable to list tenants from the landlord connection.');

            return self::FAILURE;
        }

        if ($tenants->isEmpty()) {
            if (! $this->allowsCurrentConnectionFallback()) {
                $this->error('No tenants found.');

                return self::FAILURE;
            }

            $this->info('Probing current connection.');
            if ($this->probeCurrent('Current connection')) {
                $flagged++;
            }

            return $this->finish($flagged);
        }

        $this->info("Probing {$tenants->count()} tenant(s).");

        foreach ($tenants as $tenant) {
            $tenant->makeCurrent();

            try {
                if ($this->probeCurrent("Tenant {$tenant->slug}")) {
                    $flagged++;
                }
            } finally {
                Tenant::forgetCurrent();
            }
        }

        return $this->finish($flagged);
    }

    private function finish(int $flagged): int
    {
        if ($flagged > 0) {
            $this->error("FLAG: {$flagged} tenant(s) have no Super Admin users.");

            return self::FAILURE;
        }

        $this->info('All probed tenants have at least one Super Admin user.');

        return self::SUCCESS;
    }

    /**
     * @return bool true when the tenant is flagged (zero Super Admins)
     */
    private function probeCurrent(string $scope): bool
    {
        $users = User::role(self::ROLE_NAME)->orderBy('id')->get(['id', 'name', 'email']);

        if ($users->isEmpty()) {
            $this->warn("  [FLAG] {$scope}: no users hold \"".self::ROLE_NAME.'".');

            return true;
        }

        $this->info("  {$scope}: {$users->count()} Super Admin user(s):");
        foreach ($users as $user) {
            $this->line("    - #{$user->id} {$user->email} ({$user->name})");
        }

        return false;
    }

    /**
     * @return Collection<int, Tenant>
     */
    private function tenantsToProbe(): Collection
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
}
