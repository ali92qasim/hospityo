<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\ImagingOrder;
use App\Models\ImagingOrderItem;
use App\Models\ImagingStudy;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    foreach ([
        'view investigations',
        'view investigation orders',
        'view lab results',
        'create investigations',
        'create investigation orders',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->user = User::create([
        'name' => 'Lab Surface User',
        'email' => 'lab-surface-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $this->user->givePermissionTo([
        'view investigations',
        'view investigation orders',
        'view lab results',
        'create investigations',
        'create investigation orders',
    ]);

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'Surface Patient',
        'gender' => 'female',
        'age' => 28,
        'phone' => '03001112233',
        'emergency_name' => 'Kin',
        'emergency_phone' => '03003334455',
        'emergency_relation' => 'Spouse',
    ]);

    $this->doctor = Doctor::create([
        'name' => 'Dr Surface',
        'doctor_no' => 'DOC-SURF',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005556677',
        'email' => 'surface-doctor@example.com',
        'gender' => 'male',
        'experience_years' => 4,
        'consultation_fee' => 800,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => Department::create(['name' => 'Surface', 'code' => 'SUR', 'status' => 'active'])->id,
    ]);

    $this->labTest = LabTest::create([
        'code' => 'CBC-SURF',
        'name' => 'CBC Surface',
        'category' => 'hematology',
        'sample_type' => 'blood',
        'price' => 400,
        'is_active' => true,
    ]);

    $this->imagingStudy = ImagingStudy::create([
        'code' => 'CXR-SURF',
        'name' => 'Chest X-Ray Surface',
        'category' => 'x-ray',
        'price' => 1200,
        'is_active' => true,
    ]);
});

it('lab tests data only returns lab tests', function () {
    $response = $this->getJson(route('lab.tests.data'));

    $response->assertOk();

    $names = collect($response->json('data'))->pluck('name');

    expect($names)->toContain('CBC Surface')
        ->not->toContain('Chest X-Ray Surface');
});

it('lab tests create page has no kind field and only lab categories', function () {
    $this->get(route('lab.tests.create'))
        ->assertOk()
        ->assertDontSee('name="kind"', false)
        ->assertSee('value="hematology"', false)
        ->assertDontSee('value="x-ray"', false)
        ->assertSee('Add Parameter');
});

it('lab orders create page lists only lab tests', function () {
    $this->get(route('lab.orders.create'))
        ->assertOk()
        ->assertSee('CBC Surface')
        ->assertDontSee('Chest X-Ray Surface')
        ->assertDontSee('name="kind"', false);
});

it('lab orders index only lists lab orders', function () {
    $labOrder = LabOrder::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'priority' => 'routine',
        'status' => 'ordered',
        'ordered_at' => now(),
    ]);
    LabOrderItem::create([
        'lab_order_id' => $labOrder->id,
        'lab_test_id' => $this->labTest->id,
        'quantity' => 1,
        'priority' => 'routine',
        'status' => 'ordered',
    ]);

    $imagingOrder = ImagingOrder::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'priority' => 'routine',
        'status' => 'ordered',
        'ordered_at' => now(),
    ]);
    ImagingOrderItem::create([
        'imaging_order_id' => $imagingOrder->id,
        'imaging_study_id' => $this->imagingStudy->id,
        'quantity' => 1,
        'priority' => 'routine',
        'status' => 'ordered',
    ]);

    $this->get(route('lab.orders.index'))
        ->assertOk()
        ->assertSee('CBC Surface')
        ->assertDontSee('Chest X-Ray Surface');
});

it('lab results index only lists pending lab orders', function () {
    $labOrder = LabOrder::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'priority' => 'routine',
        'status' => 'ordered',
        'ordered_at' => now(),
    ]);
    LabOrderItem::create([
        'lab_order_id' => $labOrder->id,
        'lab_test_id' => $this->labTest->id,
        'quantity' => 1,
        'priority' => 'routine',
        'status' => 'ordered',
    ]);

    $imagingOrder = ImagingOrder::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'priority' => 'routine',
        'status' => 'ordered',
        'ordered_at' => now(),
    ]);
    ImagingOrderItem::create([
        'imaging_order_id' => $imagingOrder->id,
        'imaging_study_id' => $this->imagingStudy->id,
        'quantity' => 1,
        'priority' => 'routine',
        'status' => 'ordered',
    ]);

    $this->get(route('lab.results.index'))
        ->assertOk()
        ->assertSee('CBC Surface')
        ->assertDontSee('Chest X-Ray Surface');
});

it('redirects legacy investigations index to lab tests', function () {
    $this->get(route('investigations.index'))
        ->assertRedirect(route('lab.tests.index'));
});

it('redirects legacy investigation orders index to lab orders', function () {
    $this->get(route('investigation-orders.index'))
        ->assertRedirect(route('lab.orders.index'));
});

it('redirects legacy lab results index to lab results surface', function () {
    $this->get(route('lab-results.index'))
        ->assertRedirect(route('lab.results.index'));
});
