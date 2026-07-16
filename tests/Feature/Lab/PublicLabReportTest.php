<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Investigation;
use App\Models\InvestigationOrder;
use App\Models\InvestigationOrderItem;
use App\Models\LabResult;
use App\Models\LabResultItem;
use App\Models\LabTestParameter;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    $this->user = User::create([
        'name' => 'Lab Tech',
        'email' => 'lab-public-report@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->patient = Patient::create([
        'name' => 'Jane Patient',
        'gender' => 'female',
        'age' => 30,
        'phone' => '03001112222',
        'emergency_name' => 'John Patient',
        'emergency_phone' => '03003334444',
        'emergency_relation' => 'Spouse',
    ]);

    $this->doctor = Doctor::create([
        'name' => 'Dr. Lab Ref',
        'doctor_no' => 'DOC-PUB',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005556666',
        'email' => 'doctor-pub@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => Department::create(['name' => 'Lab Pub', 'code' => 'LABP', 'status' => 'active'])->id,
    ]);

    $this->visit = Visit::create([
        'visit_no' => 'VIS-PUB-001',
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'department_id' => $this->doctor->department_id,
        'visit_type' => 'opd',
        'status' => 'active',
        'visit_datetime' => now(),
    ]);

    $this->order = InvestigationOrder::create([
        'patient_id' => $this->patient->id,
        'visit_id' => $this->visit->id,
        'doctor_id' => $this->doctor->id,
        'priority' => 'routine',
        'status' => 'reported',
        'ordered_at' => now(),
        'sample_collected_at' => now(),
        'completed_at' => now(),
    ]);

    $investigation = Investigation::create([
        'code' => 'URIC',
        'name' => 'Uric Acid',
        'category' => 'biochemistry',
        'sample_type' => 'blood',
        'price' => 500,
        'is_active' => true,
    ]);

    $parameter = LabTestParameter::create([
        'lab_test_id' => $investigation->id,
        'parameter_name' => 'Uric Acid',
        'unit' => 'mg/dL',
        'data_type' => 'numeric',
        'reference_ranges' => ['normal' => '3-7'],
        'display_order' => 1,
        'is_active' => true,
    ]);

    InvestigationOrderItem::create([
        'investigation_order_id' => $this->order->id,
        'investigation_id' => $investigation->id,
        'quantity' => 1,
        'priority' => 'routine',
        'status' => 'reported',
        'test_location' => 'indoor',
    ]);

    $result = LabResult::create([
        'investigation_order_id' => $this->order->id,
        'results' => [],
        'status' => 'final',
        'technician_id' => $this->user->id,
        'tested_at' => now(),
        'reported_at' => now(),
    ]);

    LabResultItem::create([
        'lab_result_id' => $result->id,
        'lab_test_parameter_id' => $parameter->id,
        'value' => '5.2',
        'unit' => 'mg/dL',
        'flag' => 'N',
        'entered_by' => $this->user->id,
        'entered_at' => now(),
    ]);

    $this->order->refresh();
});

it('shows the verification gate for a share token', function () {
    $this->get(route('lab-report.show', $this->order->share_token))
        ->assertOk()
        ->assertSee('Laboratory Report Access')
        ->assertSee('Patient Number')
        ->assertSee('Mobile Number');
});

it('blocks report view until credentials are verified', function () {
    $this->get(route('lab-report.view', $this->order->share_token))
        ->assertRedirect(route('lab-report.show', $this->order->share_token));
});

it('rejects incorrect patient credentials', function () {
    $this->post(route('lab-report.verify', $this->order->share_token), [
        'patient_no' => 'WRONG',
        'phone' => '03001112222',
    ])->assertSessionHasErrors('patient_no');

    $this->get(route('lab-report.view', $this->order->share_token))
        ->assertRedirect(route('lab-report.show', $this->order->share_token));
});

it('unlocks the report after matching patient number and mobile', function () {
    $this->post(route('lab-report.verify', $this->order->share_token), [
        'patient_no' => $this->patient->patient_no,
        'phone' => '03001112222',
    ])->assertRedirect(route('lab-report.view', $this->order->share_token));

    $this->get(route('lab-report.view', $this->order->share_token))
        ->assertOk()
        ->assertSee('Uric Acid')
        ->assertSee('5.2');
});

it('accepts pakistan country-code style mobile numbers', function () {
    $this->post(route('lab-report.verify', $this->order->share_token), [
        'patient_no' => $this->patient->patient_no,
        'phone' => '923001112222',
    ])->assertRedirect(route('lab-report.view', $this->order->share_token));
});

it('assigns a share token when creating an investigation order', function () {
    expect($this->order->share_token)->not->toBeEmpty()
        ->and(strlen($this->order->share_token))->toBeGreaterThanOrEqual(20);
});
