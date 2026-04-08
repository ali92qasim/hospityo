<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Emergency Department',
                'code' => 'ER',
                'description' => 'Emergency and trauma care services',
                'status' => 'active',
            ],
            [
                'name' => 'Internal Medicine',
                'code' => 'IM',
                'description' => 'General internal medicine and chronic disease management',
                'status' => 'active',
            ],
            [
                'name' => 'Pediatrics',
                'code' => 'PED',
                'description' => 'Child healthcare and development',
                'status' => 'active',
            ],
            [
                'name' => 'Obstetrics & Gynecology',
                'code' => 'OBGYN',
                'description' => 'Women\'s health and maternity services',
                'status' => 'active',
            ],
            [
                'name' => 'Surgery',
                'code' => 'SURG',
                'description' => 'General and specialized surgical procedures',
                'status' => 'active',
            ],
            [
                'name' => 'Orthopedics',
                'code' => 'ORTHO',
                'description' => 'Bone, joint, and musculoskeletal care',
                'status' => 'active',
            ],
            [
                'name' => 'Cardiology',
                'code' => 'CARD',
                'description' => 'Heart and cardiovascular care',
                'status' => 'active',
            ],
            [
                'name' => 'Neurology',
                'code' => 'NEURO',
                'description' => 'Brain and nervous system disorders',
                'status' => 'active',
            ],
            [
                'name' => 'Dermatology',
                'code' => 'DERM',
                'description' => 'Skin, hair, and nail conditions',
                'status' => 'active',
            ],
            [
                'name' => 'Radiology',
                'code' => 'RAD',
                'description' => 'Medical imaging and diagnostics',
                'status' => 'active',
            ],
            [
                'name' => 'Pathology',
                'code' => 'PATH',
                'description' => 'Laboratory testing and analysis',
                'status' => 'active',
            ],
            [
                'name' => 'Psychiatry',
                'code' => 'PSY',
                'description' => 'Mental health and behavioral disorders',
                'status' => 'active',
            ],
        ];

        foreach ($departments as $department) {
            Department::firstOrCreate(
                ['code' => $department['code']],
                $department
            );
        }
    }
}
