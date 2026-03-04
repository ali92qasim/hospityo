<?php

namespace Database\Seeders;

use App\Models\Ward;
use App\Models\Bed;
use Illuminate\Database\Seeder;

class WardSeeder extends Seeder
{
    public function run(): void
    {
        $wards = [
            [
                'name' => 'General Ward - Male',
                'ward_type' => 'general',
                'total_beds' => 20,
                'description' => 'General ward for male patients',
                'status' => 'active',
                'beds' => 20,
            ],
            [
                'name' => 'General Ward - Female',
                'ward_type' => 'general',
                'total_beds' => 20,
                'description' => 'General ward for female patients',
                'status' => 'active',
                'beds' => 20,
            ],
            [
                'name' => 'ICU',
                'ward_type' => 'icu',
                'total_beds' => 10,
                'description' => 'Intensive Care Unit',
                'status' => 'active',
                'beds' => 10,
            ],
            [
                'name' => 'Private Rooms',
                'ward_type' => 'private',
                'total_beds' => 15,
                'description' => 'Private rooms for patients',
                'status' => 'active',
                'beds' => 15,
            ],
            [
                'name' => 'Pediatric Ward',
                'ward_type' => 'general',
                'total_beds' => 12,
                'description' => 'Ward for children',
                'status' => 'active',
                'beds' => 12,
            ],
            [
                'name' => 'Maternity Ward',
                'ward_type' => 'general',
                'total_beds' => 15,
                'description' => 'Ward for maternity patients',
                'status' => 'active',
                'beds' => 15,
            ],
        ];

        foreach ($wards as $wardData) {
            $bedCount = $wardData['beds'];
            unset($wardData['beds']);
            
            $ward = Ward::create($wardData);
            
            // Create beds for this ward
            for ($i = 1; $i <= $bedCount; $i++) {
                Bed::create([
                    'ward_id' => $ward->id,
                    'bed_number' => $ward->name . '-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                    'bed_type' => $ward->ward_type,
                    'status' => 'available',
                ]);
            }
        }
    }
}
