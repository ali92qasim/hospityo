<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Lean seeder for new tenant onboarding.
 *
 * Seeds only essential reference/structural data that every hospital needs
 * to be operational from day one. No demo patients, doctors, or sample data.
 */
class TenantOnboardingSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Hospital structure
            DepartmentSeeder::class,

            // Pharmacy reference data
            MedicineCategorySeeder::class,
            MedicineBrandSeeder::class,
            UnitSeeder::class,
            PrescriptionInstructionSeeder::class,

            // Diagnostics catalog
            InvestigationSeeder::class,
            LabTestParameterSeeder::class,

            // Billing services catalog
            ServiceSeeder::class,

            // Medical reference data
            AllergySeeder::class,
        ]);
    }
}
