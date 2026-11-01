<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::findOrCreate('view patients', 'web');

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    $this->user = User::create([
        'name' => 'Patient History User',
        'email' => 'patient-history-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $this->user->givePermissionTo('view patients');

    $this->patient = Patient::create([
        'name' => 'History Patient',
        'gender' => 'male',
        'age' => 42,
        'phone' => '03001234567',
        'emergency_name' => 'Kin',
        'emergency_phone' => '03007654321',
        'emergency_relation' => 'Brother',
    ]);

    $this->doctor = Doctor::create([
        'name' => 'Dr History',
        'doctor_no' => 'DOC-HIST',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03001112233',
        'email' => 'history-doctor-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => Department::create(['name' => 'History', 'code' => 'HIS', 'status' => 'active'])->id,
    ]);

    $this->visit = Visit::create([
        'visit_no' => 'VIS-HIST-001',
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'department_id' => $this->doctor->department_id,
        'visit_type' => 'opd',
        'status' => 'completed',
        'visit_datetime' => now(),
    ]);
});

it('loads patient history when lab orders have item-level tests', function () {
    $labTest = LabTest::create([
        'code' => 'CBC-HIST',
        'name' => 'Complete Blood Count History',
        'category' => 'hematology',
        'sample_type' => 'blood',
        'price' => 500,
        'is_active' => true,
    ]);

    $order = LabOrder::create([
        'patient_id' => $this->patient->id,
        'visit_id' => $this->visit->id,
        'doctor_id' => $this->doctor->id,
        'priority' => 'routine',
        'status' => 'ordered',
        'ordered_at' => now(),
    ]);

    LabOrderItem::create([
        'lab_order_id' => $order->id,
        'lab_test_id' => $labTest->id,
        'quantity' => 1,
        'priority' => 'routine',
        'status' => 'ordered',
    ]);

    $this->actingAs($this->user)
        ->get(route('patients.history', $this->patient))
        ->assertOk()
        ->assertSee('Complete Blood Count History')
        ->assertDontSee('Call to undefined relationship');
});
