<?php

namespace Database\Seeders;

use App\Models\Designation;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    public function run(): void
    {
        $designations = [
            // Medical
            ['name' => 'Consultant', 'category' => 'medical'],
            ['name' => 'Senior Registrar', 'category' => 'medical'],
            ['name' => 'Registrar', 'category' => 'medical'],
            ['name' => 'Medical Officer', 'category' => 'medical'],
            ['name' => 'House Officer', 'category' => 'medical'],
            ['name' => 'Surgeon', 'category' => 'medical'],
            ['name' => 'Anesthetist', 'category' => 'medical'],
            ['name' => 'Radiologist', 'category' => 'medical'],
            ['name' => 'Pathologist', 'category' => 'medical'],
            ['name' => 'Dentist', 'category' => 'medical'],

            // Nursing
            ['name' => 'Head Nurse', 'category' => 'nursing'],
            ['name' => 'Charge Nurse', 'category' => 'nursing'],
            ['name' => 'Staff Nurse', 'category' => 'nursing'],
            ['name' => 'Nursing Assistant', 'category' => 'nursing'],
            ['name' => 'Midwife', 'category' => 'nursing'],

            // Admin
            ['name' => 'Hospital Administrator', 'category' => 'admin'],
            ['name' => 'HR Manager', 'category' => 'admin'],
            ['name' => 'Finance Manager', 'category' => 'admin'],
            ['name' => 'Receptionist', 'category' => 'admin'],
            ['name' => 'Billing Clerk', 'category' => 'admin'],
            ['name' => 'Medical Records Clerk', 'category' => 'admin'],

            // Technical
            ['name' => 'Lab Technician', 'category' => 'technical'],
            ['name' => 'Radiology Technician', 'category' => 'technical'],
            ['name' => 'Pharmacist', 'category' => 'technical'],
            ['name' => 'Pharmacy Assistant', 'category' => 'technical'],
            ['name' => 'IT Administrator', 'category' => 'technical'],

            // Support
            ['name' => 'Ward Boy', 'category' => 'support'],
            ['name' => 'Cleaner', 'category' => 'support'],
            ['name' => 'Security Guard', 'category' => 'support'],
            ['name' => 'Driver', 'category' => 'support'],
            ['name' => 'Cook', 'category' => 'support'],
        ];

        foreach ($designations as $data) {
            Designation::firstOrCreate(['name' => $data['name']], $data);
        }
    }
}
