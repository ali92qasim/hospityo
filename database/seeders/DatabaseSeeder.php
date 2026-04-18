<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * When run via `tenants:artisan "db:seed --database=tenant"`,
     * Tenant::checkCurrent() returns true → tenant seeders run.
     * When run directly on the landlord DB, landlord seeders run.
     */
    public function run(): void
    {
        if (Tenant::checkCurrent()) {
            $this->runTenantSeeders();
        } else {
            $this->runLandlordSeeders();
        }
    }

    /**
     * Seeders that populate a tenant database.
     */
    protected function runTenantSeeders(): void
    {
        $this->call([
            // Core System
            RolePermissionSeeder::class,

            // Hospital Structure
            DepartmentSeeder::class,

            // Diagnostics
            InvestigationSeeder::class,
            LabTestParameterSeeder::class,

            // Medical Data
            AllergySeeder::class,
        ]);

        $this->command->info('✓ Tenant seeders completed for: ' . Tenant::current()->name);
    }

    /**
     * Seeders that populate the landlord (central) database.
     */
    protected function runLandlordSeeders(): void
    {
        $this->call([
            PlanSeeder::class,
            SuperAdminSeeder::class,
            PageSeeder::class,
            SiteSettingsSeeder::class,
            PaymentGatewaySeeder::class,
        ]);

        $this->command->info('✓ Landlord seeders completed.');
    }
}
