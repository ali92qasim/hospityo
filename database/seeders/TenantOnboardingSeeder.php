<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Lean seeder for new tenant onboarding.
 * Only calls seeders that exist — safe across branches.
 */
class TenantOnboardingSeeder extends Seeder
{
    public function run(): void
    {
        $seeders = [
            DepartmentSeeder::class,
            PrescriptionInstructionSeeder::class,
            InvestigationSeeder::class,
            LabTestParameterSeeder::class,
            AllergySeeder::class,
            TaxSeeder::class,
            ChartOfAccountsSeeder::class,
            DesignationSeeder::class,
            LeaveTypeSeeder::class,
            SalaryComponentSeeder::class,
            ShiftSeeder::class,
            DocumentRequirementSeeder::class,
        ];

        foreach ($seeders as $seeder) {
            if (class_exists($seeder)) {
                $this->call($seeder);
            }
        }
    }
}
