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
use App\Models\Permission;
use App\Models\Role;

class SeedTenantData implements ShouldQueue, NotTenantAware
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
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

        $this->seedRolesAndPermissions();
        $this->seedAdminUser();
        $this->seedDefaultSettings();
        $this->seedEssentialData();

        // Mark tenant as active — provisioning complete
        $this->tenant->update(['status' => 'active']);

        Log::info("[Provisioning] ✓ Tenant '{$this->tenant->slug}' is fully provisioned and active.");

        Tenant::forgetCurrent();
    }

    /**
     * Seed the core RBAC structure every hospital needs.
     */
    protected function seedRolesAndPermissions(): void
    {
        $permissions = [
            // Patient Management
            'view patients', 'create patients', 'edit patients', 'delete patients',
            // Doctor Management
            'view doctors', 'create doctors', 'edit doctors', 'delete doctors',
            // Department Management
            'view departments', 'create departments', 'edit departments', 'delete departments',
            // Visit Management
            'view visits', 'create visits', 'edit visits', 'delete visits',
            // Appointment Management
            'view appointments', 'create appointments', 'edit appointments', 'delete appointments',
            // Medical Records
            'view medical records', 'create medical records', 'edit medical records',
            'delete medical records', 'sign medical records',
            // Billing
            'view bills', 'create bills', 'edit bills', 'delete bills',
            'create payments',
            'view services', 'create services', 'edit services', 'delete services',
            // RBAC
            'view roles', 'create roles', 'edit roles', 'delete roles',
            'view permissions', 'create permissions', 'edit permissions', 'delete permissions',
            'manage user roles',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Super Admin — full access
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // Hospital Administrator
        $admin = Role::firstOrCreate(['name' => 'Hospital Administrator', 'guard_name' => 'web']);
        $admin->syncPermissions([
            'view patients', 'create patients', 'edit patients',
            'view doctors', 'create doctors', 'edit doctors',
            'view departments', 'create departments', 'edit departments',
            'view visits', 'create visits', 'edit visits',
            'view appointments', 'create appointments', 'edit appointments',
            'view medical records', 'create medical records', 'edit medical records',
            'view bills', 'create bills', 'edit bills', 'create payments',
            'view services', 'create services', 'edit services',
            'manage user roles',
        ]);

        // Doctor
        $doctor = Role::firstOrCreate(['name' => 'Doctor', 'guard_name' => 'web']);
        $doctor->syncPermissions([
            'view patients', 'edit patients',
            'view visits', 'create visits', 'edit visits',
            'view appointments', 'create appointments', 'edit appointments',
            'view medical records', 'create medical records', 'edit medical records', 'sign medical records',
            'view bills', 'create bills',
        ]);

        // Nurse
        $nurse = Role::firstOrCreate(['name' => 'Nurse', 'guard_name' => 'web']);
        $nurse->syncPermissions([
            'view patients', 'edit patients',
            'view visits', 'edit visits',
            'view appointments',
            'view medical records', 'create medical records', 'edit medical records',
            'view bills',
        ]);

        // Receptionist
        $receptionist = Role::firstOrCreate(['name' => 'Receptionist', 'guard_name' => 'web']);
        $receptionist->syncPermissions([
            'view patients', 'create patients', 'edit patients',
            'view appointments', 'create appointments', 'edit appointments',
            'view visits', 'create visits',
            'view bills', 'create bills', 'create payments',
        ]);

        // Medical Records Clerk
        $clerk = Role::firstOrCreate(['name' => 'Medical Records Clerk', 'guard_name' => 'web']);
        $clerk->syncPermissions([
            'view patients',
            'view medical records', 'create medical records', 'edit medical records',
            'view bills',
        ]);
    }

    /**
     * Create the tenant's first admin user with Super Admin role.
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
     * Seed essential operational data via existing seeders.
     * Only structural/reference data — no demo patients or doctors.
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
