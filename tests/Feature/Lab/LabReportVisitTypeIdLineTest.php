<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabResult;
use App\Models\LabResultItem;
use App\Models\LabTest;
use App\Models\LabTestParameter;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);
    $this->withoutVite();
    Cache::flush();

    Permission::findOrCreate('view lab results', 'web');

    $this->user = User::create([
        'name' => 'Visit Id Line Printer',
        'email' => 'lab-visit-id-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $this->user->givePermissionTo('view lab results');
    $this->actingAs($this->user);

    Setting::set('hospital_name', 'Visit Id Hospital');
    Setting::set('hospital_address', '1 Identifier Lane');

    $this->department = Department::create([
        'name' => 'Visit Id Dept',
        'code' => 'VID'.uniqid(),
        'status' => 'active',
    ]);

    $this->doctor = Doctor::create([
        'name' => 'Dr Visit Id',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005550001',
        'email' => 'visit-id-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 4,
        'consultation_fee' => 900,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $this->department->id,
    ]);
});

function seedVisitIdReportOrder(object $ctx, string $visitType, string $patientName): LabOrder
{
    $patient = Patient::create([
        'name' => $patientName,
        'gender' => 'female',
        'age' => 30,
        'phone' => '0300'.random_int(1000000, 9999999),
    ]);

    $visit = Visit::create([
        'visit_no' => 'VIS-VID-'.uniqid(),
        'patient_id' => $patient->id,
        'doctor_id' => $ctx->doctor->id,
        'department_id' => $ctx->department->id,
        'visit_type' => $visitType,
        'status' => 'active',
        'visit_datetime' => now()->subDay(),
    ]);

    $order = LabOrder::create([
        'patient_id' => $patient->id,
        'visit_id' => $visit->id,
        'doctor_id' => $ctx->doctor->id,
        'priority' => 'routine',
        'status' => 'reported',
        'ordered_at' => now()->subHours(6),
        'sample_collected_at' => now()->subHours(4),
        'completed_at' => now()->subHour(),
    ]);

    $labTest = LabTest::create([
        'code' => 'VID'.uniqid(),
        'name' => 'Visit Id CBC',
        'category' => 'hematology',
        'sample_type' => 'blood',
        'price' => 500,
        'is_active' => true,
    ]);

    $parameter = LabTestParameter::create([
        'lab_test_id' => $labTest->id,
        'parameter_name' => 'Hemoglobin',
        'unit' => 'g/dL',
        'data_type' => 'numeric',
        'reference_ranges' => ['normal' => '12-16'],
        'display_order' => 1,
        'is_active' => true,
    ]);

    LabOrderItem::create([
        'lab_order_id' => $order->id,
        'lab_test_id' => $labTest->id,
        'quantity' => 1,
        'priority' => 'routine',
        'status' => 'reported',
    ]);

    $labResult = LabResult::create([
        'lab_order_id' => $order->id,
        'results' => [],
        'status' => 'final',
        'technician_id' => $ctx->user->id,
        'pathologist_id' => $ctx->user->id,
        'tested_at' => now()->subHours(2),
        'verified_at' => now()->subHour(),
        'reported_at' => now()->subHour(),
    ]);

    LabResultItem::create([
        'lab_result_id' => $labResult->id,
        'lab_test_parameter_id' => $parameter->id,
        'value' => '13.0',
        'unit' => 'g/dL',
        'flag' => 'N',
        'entered_by' => $ctx->user->id,
        'entered_at' => now()->subHours(2),
    ]);

    return $order->fresh();
}

function seedLabOnlyReportOrder(object $ctx): LabOrder
{
    $patient = Patient::create([
        'name' => 'Lab Only Patient',
        'gender' => 'male',
        'age' => 41,
        'phone' => '0300'.random_int(1000000, 9999999),
    ]);

    $order = LabOrder::create([
        'patient_id' => $patient->id,
        'visit_id' => null,
        'doctor_id' => $ctx->doctor->id,
        'priority' => 'routine',
        'status' => 'reported',
        'ordered_at' => now()->subHours(6),
        'sample_collected_at' => now()->subHours(4),
        'completed_at' => now()->subHour(),
    ]);

    $labTest = LabTest::create([
        'code' => 'LO'.uniqid(),
        'name' => 'Lab Only CBC',
        'category' => 'hematology',
        'sample_type' => 'blood',
        'price' => 500,
        'is_active' => true,
    ]);

    $parameter = LabTestParameter::create([
        'lab_test_id' => $labTest->id,
        'parameter_name' => 'Hemoglobin',
        'unit' => 'g/dL',
        'data_type' => 'numeric',
        'reference_ranges' => ['normal' => '12-16'],
        'display_order' => 1,
        'is_active' => true,
    ]);

    LabOrderItem::create([
        'lab_order_id' => $order->id,
        'lab_test_id' => $labTest->id,
        'quantity' => 1,
        'priority' => 'routine',
        'status' => 'reported',
    ]);

    $labResult = LabResult::create([
        'lab_order_id' => $order->id,
        'results' => [],
        'status' => 'final',
        'technician_id' => $ctx->user->id,
        'pathologist_id' => $ctx->user->id,
        'tested_at' => now()->subHours(2),
        'verified_at' => now()->subHour(),
        'reported_at' => now()->subHour(),
    ]);

    LabResultItem::create([
        'lab_result_id' => $labResult->id,
        'lab_test_parameter_id' => $parameter->id,
        'value' => '14.1',
        'unit' => 'g/dL',
        'flag' => 'N',
        'entered_by' => $ctx->user->id,
        'entered_at' => now()->subHours(2),
    ]);

    return $order->fresh();
}

it('omits OPD # from the patient band while keeping Patient No.', function () {
    $order = seedVisitIdReportOrder($this, 'opd', 'OPD Band Patient');

    $html = $this->get(route('investigation-orders.report', $order))
        ->assertOk()
        ->getContent();

    expect($html)->not->toContain('OPD #:')
        ->and($html)->not->toContain('IPD #:')
        ->and($html)->not->toContain('Lab #:')
        ->and($html)->toContain('Patient No.:')
        ->and($html)->toContain($order->patient->patient_no);
});

it('omits IPD # from the patient band while keeping Patient No.', function () {
    $order = seedVisitIdReportOrder($this, 'ipd', 'IPD Band Patient');

    $html = $this->get(route('investigation-orders.report', $order))
        ->assertOk()
        ->getContent();

    expect($html)->not->toContain('IPD #:')
        ->and($html)->not->toContain('OPD #:')
        ->and($html)->not->toContain('Lab #:')
        ->and($html)->toContain('Patient No.:')
        ->and($html)->toContain($order->patient->patient_no);
});

it('omits Lab # from lab-only orders while keeping Patient No.', function () {
    $order = seedLabOnlyReportOrder($this);

    $html = $this->get(route('investigation-orders.report', $order))
        ->assertOk()
        ->getContent();

    expect($html)->not->toContain('Lab #:')
        ->and($html)->not->toContain('OPD #:')
        ->and($html)->not->toContain('IPD #:')
        ->and($html)->toContain('Patient No.:')
        ->and($html)->toContain($order->patient->patient_no);
});
