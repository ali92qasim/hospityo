<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        $departments = Department::all();

        $doctors = [
            [
                'name' => 'Dr. Sarah Ahmed',
                'email' => 'doctor@hospityo.com',
                'doctor_no' => 'abc123',
                'qualification' => 'MBBS, FCPS (Medicine)',
                'specialization' => 'Internal Medicine',
                'pmdc_number' => 'PMDC-12345',
                'phone' => '0300-1234567',
                'department_id' => $departments->where('code', 'IM')->first()->id,
                'experience_years' => 15,
                'gender' => 'female',
                'consultation_fee' => 1500.00,
                'shift_start' => '08:00',
                'shift_end' => '16:00',
            ],
            [
                'name' => 'Dr. Ahmed Khan',
                'doctor_no' => 'abc234',
                'email' => 'ahmed.khan@hospityo.com',
                'qualification' => 'MBBS, FCPS (Surgery)',
                'specialization' => 'General Surgery',
                'pmdc_number' => 'PMDC-12346',
                'phone' => '0300-1234568',
                'department_id' => $departments->where('code', 'SURG')->first()->id,
                'experience_years' => 12,
                'gender' => 'male',
                'consultation_fee' => 2000.00,
                'shift_start' => '09:00',
                'shift_end' => '17:00',
            ],
            [
                'name' => 'Dr. Fatima Ali',
                'doctor_no' => 'abc345',
                'email' => 'fatima.ali@hospityo.com',
                'qualification' => 'MBBS, FCPS (Pediatrics)',
                'specialization' => 'Pediatrics',
                'pmdc_number' => 'PMDC-12347',
                'phone' => '0300-1234569',
                'department_id' => $departments->where('code', 'PED')->first()->id,
                'experience_years' => 10,
                'gender' => 'female',
                'consultation_fee' => 1200.00,
                'shift_start' => '08:00',
                'shift_end' => '16:00',
            ],
            [
                'name' => 'Dr. Maria Hassan',
                'doctor_no' => 'abc786',
                'email' => 'maria.hassan@hospityo.com',
                'qualification' => 'MBBS, FCPS (Gynecology)',
                'specialization' => 'Obstetrics & Gynecology',
                'pmdc_number' => 'PMDC-12348',
                'phone' => '0300-1234570',
                'department_id' => $departments->where('code', 'OBGYN')->first()->id,
                'experience_years' => 14,
                'gender' => 'female',
                'consultation_fee' => 1800.00,
                'shift_start' => '09:00',
                'shift_end' => '17:00',
            ],
        ];

        foreach ($doctors as $doctorData) {
            // Create user account for doctor
            $user = User::create([
                'name' => $doctorData['name'],
                'email' => $doctorData['email'],
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);

            $user->assignRole('Doctor');

            // Create doctor profile
            Doctor::create([
                'name' => $doctorData['name'],
                'email' => $doctorData['email'],
                'doctor_no' => $doctorData['doctor_no'],
                'qualification' => $doctorData['qualification'],
                'specialization' => $doctorData['specialization'],
                'pmdc_number' => $doctorData['pmdc_number'],
                'phone' => $doctorData['phone'],
                'department_id' => $doctorData['department_id'],
                'user_id' => $user->id,
                'gender' => $doctorData['gender'],
                'experience_years' => $doctorData['experience_years'],
                'consultation_fee' => $doctorData['consultation_fee'],
                'shift_start' => $doctorData['shift_start'],
                'shift_end' => $doctorData['shift_end'],
            ]);
        }
    }
}
