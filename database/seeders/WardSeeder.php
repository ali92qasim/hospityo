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
                'capacity' => 5,
                'status' => 'active',
                'department_id' => 1,
                'beds' => 20,
            ],
            [
                'name' => 'General Ward - Female',
                'ward_type' => 'general',
                'status' => 'active',
                'capacity' => 5,
                'department_id' => 1,
                'beds' => 20,
            ],
            [
                'name' => 'ICU',
                'ward_type' => 'icu',
                'status' => 'active',
                'department_id' => 1,
                'capacity' => 5,
                'beds' => 10,
            ],
            [
                'name' => 'Private Rooms',
                'ward_type' => 'private',
                'status' => 'active',
                'department_id' => 1,
                'capacity' => 5,
                'beds' => 15,
            ],
            [
                'name' => 'Pediatric Ward',
                'ward_type' => 'general',
                'status' => 'active',
                'department_id' => 1,
                'capacity' => 5,
                'beds' => 12,
            ],
            [
                'name' => 'Maternity Ward',
                'ward_type' => 'general',
                'status' => 'active',
                'department_id' => 1,
                'capacity' => 5,
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
