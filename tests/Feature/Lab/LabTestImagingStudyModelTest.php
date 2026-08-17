<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\ImagingOrder;
use App\Models\ImagingStudy;
use App\Models\LabOrder;
use App\Models\LabTest;
use App\Models\Patient;

function schemaSplitCatalogFixtures(): array
{
    $patient = Patient::create([
        'name' => 'Split Patient',
        'gender' => 'female',
        'age' => 32,
        'phone' => '03001110000',
        'emergency_name' => 'Kin',
        'emergency_phone' => '03002220000',
        'emergency_relation' => 'Spouse',
    ]);

    $doctor = Doctor::create([
        'name' => 'Dr Split',
        'doctor_no' => 'DOC-SPLIT',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03003330000',
        'email' => 'split-doctor@example.com',
        'gender' => 'male',
        'experience_years' => 4,
        'consultation_fee' => 800,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => Department::create(['name' => 'Split', 'code' => 'SPL', 'status' => 'active'])->id,
    ]);

    return [$patient, $doctor];
}

it('exposes disjoint category lists', function () {
    expect(LabTest::categories())->toContain('hematology')
        ->and(LabTest::categories())->not->toContain('x-ray')
        ->and(ImagingStudy::categories())->toContain('x-ray')
        ->and(ImagingStudy::categories())->not->toContain('hematology');
});

it('prefixes new lab orders with LAB and imaging orders with IMG', function () {
    [$patient, $doctor] = schemaSplitCatalogFixtures();

    $lab = LabOrder::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'priority' => 'routine',
        'status' => 'ordered',
        'ordered_at' => now(),
    ]);

    $img = ImagingOrder::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'priority' => 'routine',
        'status' => 'ordered',
        'ordered_at' => now(),
    ]);

    expect($lab->order_number)->toStartWith('LAB')
        ->and($img->order_number)->toStartWith('IMG')
        ->and($lab->allowsSampleCollection())->toBeTrue()
        ->and($img->allowsSampleCollection())->toBeFalse();
});
