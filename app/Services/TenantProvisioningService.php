<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Tenant;
use App\Jobs\Tenant\CreateTenantDatabase;
use App\Jobs\Tenant\MigrateTenantDatabase;
use App\Jobs\Tenant\SeedTenantData;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

class TenantProvisioningService
{
    /**
     * Provision a new tenant end-to-end.
     *
     * Creates the tenant record, then dispatches a job chain:
     *   1. Create MySQL database
     *   2. Run tenant migrations
     *   3. Seed default data (roles, permissions, admin user, settings)
     *
     * @param array $data {
     *   name: string,          — Hospital/clinic name
     *   slug?: string,         — URL slug (auto-generated if omitted)
     *   email: string,         — Admin contact email
     *   phone?: string,
     *   admin_name: string,    — First admin user's name
     *   admin_email: string,   — First admin user's email
     *   admin_password: string — First admin user's password
     * }
     * @param bool $async — If true, dispatches jobs to queue. If false, runs synchronously.
     * @return Tenant
     *
     * @throws \InvalidArgumentException
     */
    public function provision(array $data, bool $async = true): Tenant
    {
        $slug = $data['slug'] ?? Str::slug($data['name']);

        $this->validateSlug($slug);

        // Resolve the plan (default to starter if not specified)
        $planSlug = $data['plan'] ?? 'starter';
        $plan = Plan::where('slug', $planSlug)->first();

        $tenant = Tenant::create([
            'name'     => $data['name'],
            'slug'     => $slug,
            'domain'   => Tenant::domainFor($slug),
            'database' => Tenant::databaseNameFor($slug),
            'email'    => $data['email'] ?? null,
            'phone'    => $data['phone'] ?? null,
            'status'   => 'provisioning',
            'plan_id'  => $plan?->id,
            'settings' => $this->defaultSettings($data['name']),
        ]);

        $adminData = [
            'name'     => $data['admin_name'],
            'email'    => $data['admin_email'],
            'password' => $data['admin_password'],
        ];

        $jobs = [
            new CreateTenantDatabase($tenant),
            new MigrateTenantDatabase($tenant),
            new SeedTenantData($tenant, $adminData),
        ];

        if ($async) {
            Bus::chain($jobs)->dispatch();
        } else {
            foreach ($jobs as $job) {
                dispatch_sync($job);
            }
        }

        return $tenant;
    }

    /**
     * Default settings seeded into every new tenant's cache.
     */
    protected function defaultSettings(string $hospitalName): array
    {
        return [
            'hospital_name' => $hospitalName,
            'currency'      => 'PKR',
            'timezone'      => 'Asia/Karachi',
            'date_format'   => 'd/m/Y',
            'time_format'   => 'H:i',
        ];
    }

    /**
     * Validate slug uniqueness and format.
     */
    protected function validateSlug(string $slug): void
    {
        if (! preg_match('/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/', $slug)) {
            throw new \InvalidArgumentException(
                "Slug '{$slug}' is invalid. Use lowercase letters, numbers, and hyphens only."
            );
        }

        $reserved = ['www', 'app', 'api', 'admin', 'mail', 'ftp', 'staging', 'demo'];
        if (in_array($slug, $reserved, true)) {
            throw new \InvalidArgumentException("Slug '{$slug}' is reserved.");
        }

        if (Tenant::where('slug', $slug)->exists()) {
            throw new \InvalidArgumentException("Slug '{$slug}' is already taken.");
        }
    }
}
