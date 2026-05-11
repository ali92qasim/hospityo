<?php

namespace App\Jobs\Tenant;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Multitenancy\Jobs\NotTenantAware;

class SeedTenantData implements ShouldQueue, NotTenantAware
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 10;

    public function __construct(
        public Tenant $tenant,
        public array  $adminData,
    ) {}

    public function handle(): void
    {
        Log::info("[Provisioning] Seeding data for tenant: {$this->tenant->slug}");

        $this->tenant->makeCurrent();

        // Reset Spatie permission cache for this tenant's connection
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Seed all reference data + RBAC via TenantOnboardingSeeder.
        // RolePermissionSeeder is the single source of truth for permissions/roles
        // and is called first inside TenantOnboardingSeeder.
        $this->seedEssentialData();

        $this->seedAdminUser();
        $this->seedDefaultSettings();

        // Register admin email → tenant mapping in landlord DB
        \App\Models\TenantUser::register($this->adminData['email'], $this->tenant->id);

        // Mark tenant as active — provisioning complete
        $this->tenant->update(['status' => 'active']);

        Log::info("[Provisioning] ✓ Tenant '{$this->tenant->slug}' is fully provisioned and active.");

        Tenant::forgetCurrent();
    }

    /**
     * Create the tenant's first admin user with Super Admin role.
     * Must run after seedEssentialData() so the Super Admin role exists.
     */
    protected function seedAdminUser(): void
    {
        $user = User::firstOrCreate(
            ['email' => $this->adminData['email']],
            [
                'name'              => $this->adminData['name'],
                'password'          => Hash::make($this->adminData['password']),
                'email_verified_at' => now(),
            ]
        );

        if (! $user->hasRole('Super Admin')) {
            $user->assignRole('Super Admin');
        }
    }

    /**
     * Push default settings into the tenant's cache store.
     */
    protected function seedDefaultSettings(): void
    {
        $settings = $this->tenant->settings ?? [];

        foreach ($settings as $key => $value) {
            Cache::put("settings.{$key}", $value);
        }
    }

    /**
     * Seed all essential operational data via TenantOnboardingSeeder.
     * RolePermissionSeeder (RBAC) runs first inside that seeder.
     */
    protected function seedEssentialData(): void
    {
        Artisan::call('db:seed', [
            '--class'    => 'Database\\Seeders\\TenantOnboardingSeeder',
            '--database' => 'tenant',
            '--force'    => true,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error("[Provisioning] Seeding failed for tenant {$this->tenant->id}: {$e->getMessage()}");

        $this->tenant->update(['status' => 'failed']);
        Tenant::forgetCurrent();
    }
}
