<?php

use App\Models\EmergencyVisit;
use App\Models\IpdVisit;
use App\Models\OpdVisit;
use App\Models\Patient;
use App\Models\Visit;

beforeEach(function () {
    config(['visits.dual_write_enabled' => false]);
});

it('resolves opd details relation', function () {
    $patient = Patient::create([
        'name' => 'OPD Patient',
        'gender' => 'male',
        'age' => 30,
        'phone' => '03001110001',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03001110002',
        'emergency_relation' => 'Sibling',
    ]);

    $visit = Visit::create([
        'patient_id' => $patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'registered',
    ]);

    OpdVisit::create([
        'visit_id' => $visit->id,
        'queue_priority' => 'high',
    ]);

    expect($visit->fresh()->opdDetails)->toBeInstanceOf(OpdVisit::class)
        ->and($visit->opdDetails->queue_priority)->toBe('high');
});

it('resolves ipd and emergency details relations', function () {
    $patient = Patient::create([
        'name' => 'Multi Patient',
        'gender' => 'female',
        'age' => 40,
        'phone' => '03002220001',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03002220002',
        'emergency_relation' => 'Parent',
    ]);

    $ipdVisit = Visit::create([
        'patient_id' => $patient->id,
        'visit_type' => 'ipd',
        'visit_datetime' => now(),
        'status' => 'registered',
    ]);

    IpdVisit::create(['visit_id' => $ipdVisit->id]);

    $emergencyVisit = Visit::create([
        'patient_id' => $patient->id,
        'visit_type' => 'emergency',
        'visit_datetime' => now(),
        'status' => 'registered',
    ]);

    EmergencyVisit::create(['visit_id' => $emergencyVisit->id]);

    expect($ipdVisit->fresh()->ipdDetails)->toBeInstanceOf(IpdVisit::class)
        ->and($emergencyVisit->fresh()->emergencyDetails)->toBeInstanceOf(EmergencyVisit::class);
});

it('assigned doctor uses spine doctor for opd and care team for ipd', function () {
    $patient = Patient::create([
        'name' => 'Doctor Patient',
        'gender' => 'male',
        'age' => 28,
        'phone' => '03003330001',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03003330002',
        'emergency_relation' => 'Spouse',
    ]);

    $department = \App\Models\Department::create(['name' => 'Medicine', 'code' => 'MED2', 'status' => 'active']);

    $doctor = \App\Models\Doctor::create([
        'name' => 'Dr. Assigned',
        'doctor_no' => 'DOC-ASG-001',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03003330003',
        'email' => 'dr-assigned@example.com',
        'gender' => 'male',
        'experience_years' => 6,
        'consultation_fee' => 1200,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $opdVisit = Visit::create([
        'patient_id' => $patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'with_doctor',
        'doctor_id' => $doctor->id,
    ]);

    expect($opdVisit->assignedDoctor()?->id)->toBe($doctor->id);

    $ipdVisit = Visit::create([
        'patient_id' => $patient->id,
        'visit_type' => 'ipd',
        'visit_datetime' => now(),
        'status' => 'admitted',
        'doctor_id' => null,
    ]);

    expect($ipdVisit->assignedDoctor())->toBeNull();
});
