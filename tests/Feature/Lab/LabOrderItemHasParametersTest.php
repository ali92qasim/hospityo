<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabTest;
use App\Models\LabTestParameter;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    foreach ([
        'view lab results',
        'create lab results',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->user = User::create([
        'name' => 'Lab Results User',
        'email' => 'lab-has-params-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $this->user->givePermissionTo(['view lab results', 'create lab results']);

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'Has Params Patient',
        'gender' => 'female',
        'age' => 30,
        'phone' => '03001112233',
        'emergency_name' => 'Kin',
        'emergency_phone' => '03003334455',
        'emergency_relation' => 'Spouse',
    ]);

    $this->doctor = Doctor::create([
        'name' => 'Dr Params',
        'doctor_no' => 'DOC-PARAMS',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005556677',
        'email' => 'params-doctor@example.com',
        'gender' => 'male',
        'experience_years' => 4,
        'consultation_fee' => 800,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => Department::create(['name' => 'Params', 'code' => 'PRM', 'status' => 'active'])->id,
    ]);

    $this->visit = Visit::create([
        'visit_no' => 'VIS-PARAMS-001',
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'department_id' => $this->doctor->department_id,
        'visit_type' => 'opd',
        'status' => 'active',
        'visit_datetime' => now(),
    ]);
});

function makeHasParametersLabTest(string $code, string $name): LabTest
{
    return LabTest::create([
        'code' => $code,
        'name' => $name,
        'category' => 'biochemistry',
        'sample_type' => 'blood',
        'price' => 500,
        'is_active' => true,
    ]);
}

function makeHasParametersPendingItem(LabTest $labTest): LabOrderItem
{
    $order = LabOrder::create([
        'patient_id' => test()->patient->id,
        'visit_id' => test()->visit->id,
        'doctor_id' => test()->doctor->id,
        'priority' => 'routine',
        'status' => 'ordered',
        'ordered_at' => now(),
    ]);

    return LabOrderItem::create([
        'lab_order_id' => $order->id,
        'lab_test_id' => $labTest->id,
        'quantity' => 1,
        'priority' => 'routine',
        'status' => 'ordered',
    ]);
}

it('reports true when the related lab test has parameters', function () {
    $labTest = makeHasParametersLabTest('CBC-HP', 'CBC With Params');
    LabTestParameter::create([
        'lab_test_id' => $labTest->id,
        'parameter_name' => 'Hemoglobin',
        'unit' => 'g/dL',
        'data_type' => 'numeric',
        'reference_ranges' => ['normal' => '12-16'],
        'display_order' => 1,
        'is_active' => true,
    ]);

    $item = makeHasParametersPendingItem($labTest);

    expect($item->hasParameters())->toBeTrue();
});

it('reports false when the related lab test has no parameters', function () {
    $labTest = makeHasParametersLabTest('CULT-HP', 'Culture Text Result');
    $item = makeHasParametersPendingItem($labTest);

    expect($item->hasParameters())->toBeFalse();
});

it('reports false when the related lab test is missing', function () {
    $labTest = makeHasParametersLabTest('MISS-HP', 'Missing Relation');
    $item = makeHasParametersPendingItem($labTest);
    $item->setRelation('investigation', null);
    $item->setRelation('labTest', null);

    expect($item->hasParameters())->toBeFalse();
});

it('renders the batch results page for a parameterized pending test', function () {
    $labTest = makeHasParametersLabTest('LFT-HP', 'Liver Function');
    LabTestParameter::create([
        'lab_test_id' => $labTest->id,
        'parameter_name' => 'ALT',
        'unit' => 'U/L',
        'data_type' => 'numeric',
        'reference_ranges' => ['normal' => '7-56'],
        'display_order' => 1,
        'is_active' => true,
    ]);
    makeHasParametersPendingItem($labTest);

    $this->get(route('lab-results.create-batch', [
        'patient_id' => $this->patient->id,
        'visit_id' => $this->visit->id,
    ]))
        ->assertOk()
        ->assertSee('Liver Function')
        ->assertSee('ALT')
        ->assertDontSee('Call to undefined method');
});
