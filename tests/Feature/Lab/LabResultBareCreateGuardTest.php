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
    foreach (['view lab results', 'create lab results'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->user = User::create([
        'name' => 'Lab Create Guard',
        'email' => 'lab-create-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $this->user->givePermissionTo(['view lab results', 'create lab results']);

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);
    $this->withoutVite();
    $this->actingAs($this->user);
});

it('does not expose a resource lab-results.create action that 500s without an order item', function () {
    $response = $this->get('/lab-results/create');

    expect($response->status())->not->toBe(500);
    $response->assertRedirect(route('lab.results.index'));
});

it('still opens result entry when bound to a lab order item', function () {
    $patient = Patient::create([
        'name' => 'Bound Patient',
        'gender' => 'female',
        'age' => 30,
        'phone' => '03001234567',
        'emergency_name' => 'Kin',
        'emergency_phone' => '03003334455',
        'emergency_relation' => 'Spouse',
    ]);

    $doctor = Doctor::create([
        'name' => 'Dr Bound',
        'doctor_no' => 'DOC-BOUND',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005556677',
        'email' => 'bound-doctor-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 4,
        'consultation_fee' => 800,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => Department::create(['name' => 'Bound', 'code' => 'BND'.uniqid(), 'status' => 'active'])->id,
    ]);

    $visit = Visit::create([
        'visit_no' => 'VIS-BOUND-'.uniqid(),
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'department_id' => $doctor->department_id,
        'visit_type' => 'opd',
        'status' => 'active',
        'visit_datetime' => now(),
    ]);

    $labTest = LabTest::create([
        'code' => 'CBC-BND-'.uniqid(),
        'name' => 'Bound CBC',
        'category' => 'hematology',
        'sample_type' => 'blood',
        'price' => 500,
        'is_active' => true,
    ]);

    $order = LabOrder::create([
        'patient_id' => $patient->id,
        'visit_id' => $visit->id,
        'doctor_id' => $doctor->id,
        'priority' => 'routine',
        'status' => 'ordered',
        'ordered_at' => now(),
    ]);

    $item = LabOrderItem::create([
        'lab_order_id' => $order->id,
        'lab_test_id' => $labTest->id,
        'quantity' => 1,
        'priority' => 'routine',
        'status' => 'ordered',
    ]);

    $this->get(route('lab-orders.results.create', $item))->assertOk();
});
