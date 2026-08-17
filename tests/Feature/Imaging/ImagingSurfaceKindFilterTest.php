<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\ImagingOrder;
use App\Models\ImagingOrderItem;
use App\Models\ImagingReport;
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
        'view radiology results',
        'create investigations',
        'create investigation orders',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->user = User::create([
        'name' => 'Imaging Surface User',
        'email' => 'img-surface-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $this->user->givePermissionTo([
        'view investigations',
        'view investigation orders',
        'view radiology results',
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
        'name' => 'Imaging Patient',
        'gender' => 'male',
        'age' => 35,
        'phone' => '03009998877',
        'emergency_name' => 'Kin',
        'emergency_phone' => '03008887766',
        'emergency_relation' => 'Brother',
    ]);

    $this->doctor = Doctor::create([
        'name' => 'Dr Imaging',
        'doctor_no' => 'DOC-IMG',
        'specialization' => 'Radiology',
        'qualification' => 'MBBS',
        'phone' => '03007776655',
        'email' => 'imaging-doctor@example.com',
        'gender' => 'female',
        'experience_years' => 6,
        'consultation_fee' => 1500,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => Department::create(['name' => 'Imaging', 'code' => 'IMG', 'status' => 'active'])->id,
    ]);

    $this->labTest = LabTest::create([
        'code' => 'LFT-IMG',
        'name' => 'LFT Imaging Filter',
        'category' => 'biochemistry',
        'sample_type' => 'blood',
        'price' => 500,
        'is_active' => true,
    ]);

    $this->imagingStudy = ImagingStudy::create([
        'code' => 'US-IMG',
        'name' => 'Ultrasound Imaging Filter',
        'category' => 'ultrasound',
        'price' => 2000,
        'is_active' => true,
    ]);
});

it('imaging studies data only returns imaging studies', function () {
    $response = $this->getJson(route('imaging.studies.data'));

    $response->assertOk();

    $names = collect($response->json('data'))->pluck('name');

    expect($names)->toContain('Ultrasound Imaging Filter')
        ->not->toContain('LFT Imaging Filter');
});

it('imaging studies create page has no kind, lab categories, parameters, or sample type', function () {
    $this->get(route('imaging.studies.create'))
        ->assertOk()
        ->assertDontSee('name="kind"', false)
        ->assertSee('value="x-ray"', false)
        ->assertDontSee('value="hematology"', false)
        ->assertDontSee('Add Parameter')
        ->assertDontSee('Sample Type');
});

it('imaging orders index only lists imaging orders and has no Collect Sample', function () {
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

    $this->get(route('imaging.orders.index'))
        ->assertOk()
        ->assertSee('Ultrasound Imaging Filter')
        ->assertDontSee('LFT Imaging Filter')
        ->assertDontSee('Collect Sample');
});

it('imaging reports index only lists imaging order results', function () {
    $labOrder = LabOrder::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'priority' => 'routine',
        'status' => 'reported',
        'ordered_at' => now(),
    ]);
    LabOrderItem::create([
        'lab_order_id' => $labOrder->id,
        'lab_test_id' => $this->labTest->id,
        'quantity' => 1,
        'priority' => 'routine',
        'status' => 'reported',
    ]);

    $imagingOrder = ImagingOrder::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'priority' => 'routine',
        'status' => 'reported',
        'ordered_at' => now(),
    ]);
    ImagingOrderItem::create([
        'imaging_order_id' => $imagingOrder->id,
        'imaging_study_id' => $this->imagingStudy->id,
        'quantity' => 1,
        'priority' => 'routine',
        'status' => 'reported',
    ]);

    ImagingReport::create([
        'imaging_order_id' => $imagingOrder->id,
        'report_text' => 'Imaging report body',
        'status' => 'final',
        'radiologist_id' => $this->user->id,
        'reported_at' => now(),
    ]);

    $this->get(route('imaging.reports.index'))
        ->assertOk()
        ->assertSee('Ultrasound Imaging Filter')
        ->assertDontSee('LFT Imaging Filter');
});

it('redirects legacy radiology results index to imaging reports', function () {
    $this->get(route('radiology-results.index'))
        ->assertRedirect(route('imaging.reports.index'));
});
