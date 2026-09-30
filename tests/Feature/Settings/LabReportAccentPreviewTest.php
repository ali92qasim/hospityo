<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabResult;
use App\Models\LabResultItem;
use App\Models\LabTest;
use App\Models\LabTestParameter;
use App\Models\ModuleRegistry;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Support\LabReportPrintSettings;
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
});

function labReportAccentPreviewUser(): User
{
    Permission::findOrCreate('access settings.lab-report-print', 'web');

    $user = User::create([
        'name' => 'Accent Preview User',
        'email' => 'lab-accent-preview-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $user->givePermissionTo('access settings.lab-report-print');

    return $user;
}

it('registers settings.lab-report-print.preview under the lab-report-print module group', function () {
    expect(ModuleRegistry::moduleForRoute('settings.lab-report-print.preview'))
        ->toBe('settings.lab-report-print');
});

it('renders fixture preview when tenant has no printable lab order', function () {
    $this->actingAs(labReportAccentPreviewUser());

    $this->get(route('settings.lab-report-print.preview', ['accent' => '#123456']))
        ->assertOk()
        ->assertSee('--lab-report-accent: #123456', false)
        ->assertSee('LAB REPORT', false)
        ->assertSee('Preview Sample Patient', false);
});

it('prefers a real printable order over the fixture when one exists', function () {
    $this->actingAs(labReportAccentPreviewUser());

    $patient = Patient::create([
        'name' => 'REAL-PREVIEW-PATIENT',
        'gender' => 'female',
        'age' => 34,
        'phone' => '03001112233',
    ]);

    $department = Department::create([
        'name' => 'Preview Dept',
        'code' => 'PRV'.uniqid(),
        'status' => 'active',
    ]);

    $doctor = Doctor::create([
        'name' => 'Dr Preview Order',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005550001',
        'email' => 'preview-order-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $visit = Visit::create([
        'visit_no' => 'VIS-PRV-001',
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'department_id' => $department->id,
        'visit_type' => 'opd',
        'status' => 'active',
        'visit_datetime' => now()->subDay(),
    ]);

    $order = LabOrder::create([
        'patient_id' => $patient->id,
        'visit_id' => $visit->id,
        'doctor_id' => $doctor->id,
        'priority' => 'routine',
        'status' => 'reported',
        'ordered_at' => now()->subHours(6),
        'sample_collected_at' => now()->subHours(4),
        'completed_at' => now()->subHour(),
    ]);

    $labTest = LabTest::create([
        'code' => 'PRV',
        'name' => 'Preview CBC',
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

    $user = labReportAccentPreviewUser();

    $labResult = LabResult::create([
        'lab_order_id' => $order->id,
        'results' => [],
        'status' => 'final',
        'technician_id' => $user->id,
        'pathologist_id' => $user->id,
        'tested_at' => now()->subHours(2),
        'verified_at' => now()->subHour(),
        'reported_at' => now()->subHour(),
    ]);

    LabResultItem::create([
        'lab_result_id' => $labResult->id,
        'lab_test_parameter_id' => $parameter->id,
        'value' => '13.1',
        'unit' => 'g/dL',
        'flag' => 'N',
        'entered_by' => $user->id,
        'entered_at' => now()->subHours(2),
    ]);

    $this->get(route('settings.lab-report-print.preview'))
        ->assertOk()
        ->assertSee('REAL-PREVIEW-PATIENT', false)
        ->assertDontSee('Preview Sample Patient', false);
});

it('accent query on preview does not persist lab_report_print accent_color', function () {
    $this->actingAs(labReportAccentPreviewUser());

    LabReportPrintSettings::put([
        ...LabReportPrintSettings::DEFAULTS,
        'accent_color' => '#0F766E',
    ]);

    $this->get(route('settings.lab-report-print.preview', ['accent' => '#ABCDEF']))->assertOk();

    expect(LabReportPrintSettings::get()['accent_color'])->toBe('#0F766E');
});
