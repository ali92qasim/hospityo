<?php

use App\Models\Consultation;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\Visit;
use App\Services\PrescriptionPrintFieldResolver;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->patient = Patient::create([
        'name' => 'Ali Khan',
        'gender' => 'male',
        'age' => 42,
        'phone' => '03001112233',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03004445566',
        'emergency_relation' => 'Brother',
    ]);

    $department = Department::create(['name' => 'OPD', 'code' => 'OPD-RES', 'status' => 'active']);

    $this->doctor = Doctor::create([
        'name' => 'Sara Ahmed',
        'specialization' => 'Medicine',
        'qualification' => 'MBBS, FCPS',
        'pmdc_number' => '12345-P',
        'phone' => '03006667788',
        'email' => 'sara-print-'.uniqid().'@example.com',
        'gender' => 'female',
        'experience_years' => 8,
        'consultation_fee' => 1500,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $this->visit = Visit::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'visit_type' => 'opd',
        'status' => 'active',
        'visit_datetime' => now()->setTime(14, 30),
    ]);

    Consultation::create([
        'visit_id' => $this->visit->id,
        'provisional_diagnosis' => 'Viral fever',
        'presenting_complaints' => 'Fever for 3 days',
        'treatment_plan' => 'Rest and fluids',
        'follow_up_instructions' => 'Return if worse',
        'next_visit_date' => now()->addDays(5)->toDateString(),
    ]);

    Setting::set('hospital_name', 'City Care Hospital');
    Cache::forget('settings.hospital_name');
});

it('resolves patient, doctor, and diagnosis fields from the visit graph', function () {
    $resolver = new PrescriptionPrintFieldResolver;
    $visit = $this->visit->fresh();

    expect($resolver->resolve($visit, 'patient_name'))->toBe('Ali Khan')
        ->and($resolver->resolve($visit, 'age'))->toBe('42')
        ->and($resolver->resolve($visit, 'age_gender'))->toBe('42Y / Male')
        ->and($resolver->resolve($visit, 'doctor_name'))->toBe('Dr. Sara Ahmed')
        ->and($resolver->resolve($visit, 'doctor_registration_no'))->toBe('12345-P')
        ->and($resolver->resolve($visit, 'diagnosis'))->toBe('Viral fever')
        ->and($resolver->resolve($visit, 'hospital_name'))->toBe('City Care Hospital')
        ->and($resolver->resolve($visit, 'not_a_real_field'))->toBeNull();
});
