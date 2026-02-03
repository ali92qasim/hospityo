<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['name' => 'Consultation Fee', 'code' => 'CONS001', 'price' => 1500, 'category' => 'consultation', 'is_active' => true],
            ['name' => 'Blood Test (CBC)', 'code' => 'LAB001', 'price' => 800, 'category' => 'lab_test', 'is_active' => true],
            ['name' => 'X-Ray Chest', 'code' => 'RAD001', 'price' => 1200, 'category' => 'imaging', 'is_active' => true],
            ['name' => 'ECG', 'code' => 'DIAG001', 'price' => 600, 'category' => 'procedure', 'is_active' => true],
            ['name' => 'Ultrasound', 'code' => 'RAD002', 'price' => 2000, 'category' => 'imaging', 'is_active' => true],
            ['name' => 'CT Scan', 'code' => 'RAD003', 'price' => 8000, 'category' => 'imaging', 'is_active' => true],
            ['name' => 'MRI', 'code' => 'RAD004', 'price' => 15000, 'category' => 'imaging', 'is_active' => true],
            ['name' => 'Urine Test', 'code' => 'LAB002', 'price' => 400, 'category' => 'lab_test', 'is_active' => true],
            ['name' => 'Blood Sugar Test', 'code' => 'LAB003', 'price' => 300, 'category' => 'lab_test', 'is_active' => true],
            ['name' => 'Liver Function Test', 'code' => 'LAB004', 'price' => 1500, 'category' => 'lab_test', 'is_active' => true],
            ['name' => 'Kidney Function Test', 'code' => 'LAB005', 'price' => 1200, 'category' => 'lab_test', 'is_active' => true],
            ['name' => 'Thyroid Test', 'code' => 'LAB006', 'price' => 2500, 'category' => 'lab_test', 'is_active' => true],
            ['name' => 'Emergency Consultation', 'code' => 'EMR001', 'price' => 3000, 'category' => 'consultation', 'is_active' => true],
            ['name' => 'Dressing', 'code' => 'PROC001', 'price' => 500, 'category' => 'procedure', 'is_active' => true],
            ['name' => 'Injection', 'code' => 'PROC002', 'price' => 200, 'category' => 'procedure', 'is_active' => true],
            ['name' => 'IV Drip', 'code' => 'PROC003', 'price' => 800, 'category' => 'procedure', 'is_active' => true],
            ['name' => 'Physiotherapy Session', 'code' => 'THER001', 'price' => 1000, 'category' => 'procedure', 'is_active' => true],
            ['name' => 'Dental Consultation', 'code' => 'DENT001', 'price' => 1200, 'category' => 'consultation', 'is_active' => true],
            ['name' => 'Eye Examination', 'code' => 'OPHT001', 'price' => 1000, 'category' => 'consultation', 'is_active' => true],
            ['name' => 'Vaccination', 'code' => 'IMMU001', 'price' => 1500, 'category' => 'medication', 'is_active' => true]
        ];

        foreach ($services as $service) {
            Service::firstOrCreate(
                ['code' => $service['code']],
                $service
            );
        }
    }
}