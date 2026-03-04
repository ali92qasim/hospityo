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
                'qualification' => 'MBBS, FCPS (Medicine)',
                'specialization' => 'Internal Medicine',
                'pmdc_number' => 'PMDC-12345',
                'phone' => '0300-1234567',
                'department_id' => $departments->where('code', 'IM')->first()->id,
                'experience_years' => 15,
            ],
            [
                'name' => 'Dr. Ahmed Khan',
                'email' => 'ahmed.khan@hospityo.com',
                'qualification' => 'MBBS, FCPS (Surgery)',
                'specialization' => 'General Surgery',
                'pmdc_number' => 'PMDC-12346',
                'phone' => '0300-1234568',
                'department_id' => $departments->where('code', 'SURG')->first()->id,
                'experience_years' => 12,
            ],
            [
                'name' => 'Dr. Fatima Ali',
                'email' => 'fatima.ali@hospityo.com',
                'qualification' => 'MBBS, FCPS (Pediatrics)',
                'specialization' => 'Pediatrics',
                'pmdc_number' => 'PMDC-12347',
                'phone' => '0300-1234569',
                'department_id' => $departments->where('code', 'PED')->first()->id,
                'experience_years' => 10,
            ],
            [
                'name' => 'Dr. Maria Hassan',
                'email' => 'maria.hassan@hospityo.com',
                'qualification' => 'MBBS, FCPS (Gynecology)',
                'specialization' => 'Obstetrics & Gynecology',
                'pmdc_number' => 'PMDC-12348',
                'phone' => '0300-1234570',
                'department_id' => $departments->where('code', 'OBGYN')->first()->id,
                'experience_years' => 14,
            ],
            [
                'name' => 'Dr. Usman Malik',
                'email' => 'usman.malik@hospityo.com',
                'qualification' => 'MBBS, FCPS (Orthopedics)',
                'specialization' => 'Orthopedic Surgery',
                'pmdc_number' => 'PMDC-12349',
                'phone' => '0300-1234571',
                'department_id' => $departments->where('code', 'ORTHO')->first()->id,
                'experience_years' => 11,
            ],
            [
                'name' => 'Dr. Ayesha Siddiqui',
                'email' => 'ayesha.siddiqui@hospityo.com',
                'qualification' => 'MBBS, FCPS (Cardiology)',
                'specialization' => 'Cardiology',
                'pmdc_number' => 'PMDC-12350',
                'phone' => '0300-1234572',
                'department_id' => $departments->where('code', 'CARD')->first()->id,
                'experience_years' => 13,
            ],
            [
                'name' => 'Dr. Hassan Raza',
                'email' => 'hassan.raza@hospityo.com',
                'qualification' => 'MBBS, FCPS (Neurology)',
                'specialization' => 'Neurology',
                'pmdc_number' => 'PMDC-12351',
                'phone' => '0300-1234573',
                'department_id' => $departments->where('code', 'NEURO')->first()->id,
                'experience_years' => 9,
            ],
            [
                'name' => 'Dr. Zainab Tariq',
                'email' => 'zainab.tariq@hospityo.com',
                'qualification' => 'MBBS, FCPS (Dermatology)',
                'specialization' => 'Dermatology',
                'pmdc_number' => 'PMDC-12352',
                'phone' => '0300-1234574',
                'department_id' => $departments->where('code', 'DERM')->first()->id,
                'experience_years' => 8,
            ],
            [
                'name' => 'Dr. Ali Raza',
                'email' => 'ali.raza@hospityo.com',
                'qualification' => 'MBBS, FCPS (Emergency Medicine)',
                'specialization' => 'Emergency Medicine',
                'pmdc_number' => 'PMDC-12353',
                'phone' => '0300-1234575',
                'department_id' => $departments->where('code', 'ER')->first()->id,
                'experience_years' => 7,
            ],
            [
                'name' => 'Dr. Sana Iqbal',
                'email' => 'sana.iqbal@hospityo.com',
                'qualification' => 'MBBS, FCPS (Psychiatry)',
                'specialization' => 'Psychiatry',
                'pmdc_number' => 'PMDC-12354',
                'phone' => '0300-1234576',
                'department_id' => $departments->where('code', 'PSY')->first()->id,
                'experience_years' => 10,
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
            $doctor = Doctor::create([
                'name' => $doctorData['name'],
                'qualification' => $doctorData['qualification'],
                'specialization' => $doctorData['specialization'],
                'pmdc_number' => $doctorData['pmdc_number'],
                'phone' => $doctorData['phone'],
                'department_id' => $doctorData['department_id'],
                'user_id' => $user->id,
            ]);
        }
    }
}
