<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\ImagingOrder;
use App\Models\ImagingStudy;
use App\Models\LabOrder;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\User;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Order Kind User',
        'email' => 'order-kind@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->patient = Patient::create([
        'name' => 'Order Patient',
        'gender' => 'male',
        'age' => 40,
        'phone' => '03001110000',
        'emergency_name' => 'Kin',
        'emergency_phone' => '03002220000',
        'emergency_relation' => 'Brother',
    ]);

    $this->doctor = Doctor::create([
        'name' => 'Dr Order',
        'doctor_no' => 'DOC-ORD',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03003330000',
        'email' => 'order-doctor@example.com',
        'gender' => 'male',
        'experience_years' => 4,
        'consultation_fee' => 800,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => Department::create(['name' => 'Orders', 'code' => 'ORD', 'status' => 'active'])->id,
    ]);
});

it('prefixes lab orders with LAB and imaging orders with IMG', function () {
    $lab = LabOrder::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'priority' => 'routine',
        'status' => 'ordered',
        'ordered_at' => now(),
    ]);

    $img = ImagingOrder::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'priority' => 'routine',
        'status' => 'ordered',
        'ordered_at' => now(),
    ]);

    expect($lab->order_number)->toStartWith('LAB')
        ->and($img->order_number)->toStartWith('IMG');
});

it('keeps lab tests and imaging studies in separate catalogs', function () {
    $lab = LabTest::create([
        'code' => 'CBC-ORD',
        'name' => 'CBC Order',
        'category' => 'hematology',
        'sample_type' => 'blood',
        'price' => 400,
        'is_active' => true,
    ]);

    $imaging = ImagingStudy::create([
        'code' => 'CXR-ORD',
        'name' => 'Chest X-Ray Order',
        'category' => 'x-ray',
        'price' => 1200,
        'is_active' => true,
    ]);

    expect(LabTest::query()->whereKey($lab->id)->exists())->toBeTrue()
        ->and(ImagingStudy::query()->whereKey($imaging->id)->exists())->toBeTrue()
        ->and(LabTest::query()->where('code', 'CXR-ORD')->exists())->toBeFalse()
        ->and(ImagingStudy::query()->where('code', 'CBC-ORD')->exists())->toBeFalse();
});
