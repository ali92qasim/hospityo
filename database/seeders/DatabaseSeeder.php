<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Core System
            RolePermissionSeeder::class,
            UserSeeder::class,
            
            // Hospital Structure
            DepartmentSeeder::class,
            DoctorSeeder::class,
            WardSeeder::class,
            
            // Patients
            PatientSeeder::class,
            
            // Pharmacy
            MedicineCategorySeeder::class,
            MedicineBrandSeeder::class,
            UnitSeeder::class,
            MedicineSeeder::class,
            PrescriptionInstructionSeeder::class,
            SupplierSeeder::class,
            
            // Diagnostics
            InvestigationSeeder::class,
            LabTestParameterSeeder::class,
            
            // Billing
            ServiceSeeder::class,
            
            // Medical Data
            AllergySeeder::class,
        ]);
        
        $this->command->info('✓ All seeders completed successfully!');
        $this->command->info('');
        $this->command->info('Sample Data Summary:');
        $this->command->info('- 12 Departments');
        $this->command->info('- 10 Doctors with user accounts');
        $this->command->info('- 15 Patients');
        $this->command->info('- 6 Wards with 92 Beds');
        $this->command->info('- 12 Medicine Categories');
        $this->command->info('- 12 Medicine Brands');
        $this->command->info('- 20 Medicines with SKU');
        $this->command->info('- 5 Suppliers');
        $this->command->info('- Investigation tests and parameters');
        $this->command->info('- Services and allergies');
        $this->command->info('');
        $this->command->info('Demo Credentials:');
        $this->command->info('Admin: admin@hospityo.com / password');
        $this->command->info('Doctor: doctor@hospityo.com / password');
    }
}
