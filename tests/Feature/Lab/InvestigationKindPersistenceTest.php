<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Kind Persist',
        'email' => 'kind-persist@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->patient = Patient::create([
        'name' => 'Persist Patient',
        'gender' => 'female',
        'age' => 30,
        'phone' => '03001112222',
        'emergency_name' => 'Kin',
        'emergency_phone' => '03003334444',
        'emergency_relation' => 'Spouse',
    ]);

    $this->doctor = Doctor::create([
        'name' => 'Dr Persist',
        'doctor_no' => 'DOC-PERSIST',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005556666',
        'email' => 'persist-doctor@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => Department::create(['name' => 'Persist', 'code' => 'PER', 'status' => 'active'])->id,
    ]);
});

it('stores lab tests without a kind column', function () {
    $labTest = LabTest::create([
        'code' => 'CBC-PERSIST',
        'name' => 'CBC Persist',
        'category' => 'hematology',
        'sample_type' => 'blood',
        'price' => 400,
        'is_active' => true,
    ]);

    expect($labTest->fresh()->category)->toBe('hematology')
        ->and(\Illuminate\Support\Facades\Schema::connection('tenant')->hasColumn('lab_tests', 'kind'))->toBeFalse();
});

it('stores lab orders without a kind column', function () {
    $lab = LabTest::create([
        'code' => 'LFT-PERSIST',
        'name' => 'LFT Persist',
        'category' => 'biochemistry',
        'sample_type' => 'blood',
        'price' => 500,
        'is_active' => true,
    ]);

    $order = LabOrder::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'priority' => 'routine',
        'status' => 'ordered',
        'ordered_at' => now(),
    ]);

    LabOrderItem::create([
        'lab_order_id' => $order->id,
        'lab_test_id' => $lab->id,
        'quantity' => 1,
        'priority' => 'routine',
        'status' => 'ordered',
    ]);

    expect($order->fresh()->items)->toHaveCount(1)
        ->and(\Illuminate\Support\Facades\Schema::connection('tenant')->hasColumn('lab_orders', 'kind'))->toBeFalse();
});
