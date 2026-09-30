<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabReportRosterDoctor;
use App\Models\LabResult;
use App\Models\LabResultItem;
use App\Models\LabTest;
use App\Models\LabTestParameter;
use App\Models\Patient;
use App\Models\Setting;
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

    Permission::findOrCreate('view lab results', 'web');

    $this->user = User::create([
        'name' => 'Accent Chrome Printer',
        'email' => 'lab-accent-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $this->user->givePermissionTo('view lab results');
    $this->actingAs($this->user);

    Setting::set('hospital_name', 'Accent City Hospital');
    Setting::set('hospital_address', '11 Accent Avenue');

    $this->patient = Patient::create([
        'name' => 'Accent Patient',
        'gender' => 'female',
        'age' => 28,
        'phone' => '03009991122',
    ]);

    $department = Department::create([
        'name' => 'Accent Dept',
        'code' => 'ACC'.uniqid(),
        'status' => 'active',
    ]);

    $this->doctor = Doctor::create([
        'name' => 'Dr Ordering Accent',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005551111',
        'email' => 'ordering-accent-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 4,
        'consultation_fee' => 900,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $this->reviewer = Doctor::create([
        'name' => 'Dr Review Accent',
        'specialization' => 'Pathology',
        'qualification' => 'FCPS',
        'phone' => '03005552222',
        'email' => 'review-accent-'.uniqid().'@example.com',
        'gender' => 'female',
        'experience_years' => 9,
        'consultation_fee' => 1500,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    LabReportRosterDoctor::create([
        'doctor_id' => $this->reviewer->id,
        'sort_order' => 0,
    ]);

    $this->visit = Visit::create([
        'visit_no' => 'VIS-ACC-001',
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'department_id' => $department->id,
        'visit_type' => 'opd',
        'status' => 'active',
        'visit_datetime' => now()->subDay()->setTime(10, 30),
    ]);

    $this->order = LabOrder::create([
        'patient_id' => $this->patient->id,
        'visit_id' => $this->visit->id,
        'doctor_id' => $this->doctor->id,
        'priority' => 'routine',
        'status' => 'reported',
        'ordered_at' => now()->subHours(6),
        'sample_collected_at' => now()->subHours(4),
        'completed_at' => now()->subHour(),
        'clinical_notes' => 'Accent chrome fixture note.',
    ]);

    $labTest = LabTest::create([
        'code' => 'CBC',
        'name' => 'Complete Blood Count',
        'category' => 'hematology',
        'sample_type' => 'blood',
        'price' => 800,
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
        'lab_order_id' => $this->order->id,
        'lab_test_id' => $labTest->id,
        'quantity' => 1,
        'priority' => 'routine',
        'status' => 'reported',
    ]);

    $this->labResult = LabResult::create([
        'lab_order_id' => $this->order->id,
        'results' => [],
        'status' => 'final',
        'technician_id' => $this->user->id,
        'pathologist_id' => $this->user->id,
        'tested_at' => now()->subHours(2),
        'verified_at' => now()->subHour(),
        'reported_at' => now()->subHour(),
    ]);

    $this->labResult->reviewers()->sync([
        $this->reviewer->id => ['sort_order' => 0],
    ]);

    LabResultItem::create([
        'lab_result_id' => $this->labResult->id,
        'lab_test_parameter_id' => $parameter->id,
        'value' => '11.2',
        'unit' => 'g/dL',
        'flag' => 'L',
        'entered_by' => $this->user->id,
        'entered_at' => now()->subHours(2),
    ]);

    $this->order->refresh();
});

it('emits --lab-report-accent from saved settings on the report', function () {
    LabReportPrintSettings::put([
        ...LabReportPrintSettings::DEFAULTS,
        'accent_color' => '#123456',
    ]);

    $html = $this->get(route('investigation-orders.report', $this->order))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('--lab-report-accent: #123456')
        ->and($html)->toMatch('/\.header\s*\{[^}]*border-bottom:\s*2px solid var\(--lab-report-accent\)/s')
        ->and($html)->toMatch('/\.test-panel-header\s*\{[^}]*background:\s*var\(--lab-report-accent\)/s')
        ->and($html)->toMatch('/\.test-panel-header\s*\{[^}]*color:\s*#fff/s')
        ->and($html)->toMatch('/\.patient-box\s*\{[^}]*border:\s*1px solid var\(--lab-report-accent\)/s')
        ->and($html)->toMatch('/\.test-panel\s*\{[^}]*border:\s*1px solid var\(--lab-report-accent\)/s')
        ->and($html)->toMatch('/\.comments-box\s*\{[^}]*border:\s*1px solid var\(--lab-report-accent\)/s')
        ->and($html)->toMatch('/\.reviewer-block\s*\{[^}]*border:\s*1px solid var\(--lab-report-accent\)/s')
        ->and($html)->toMatch('/\.signature-line\s*\{[^}]*border-top:\s*1px solid var\(--lab-report-accent\)/s');
});

it('does not accent results-table or clinical abnormal classes', function () {
    $html = $this->get(route('investigation-orders.report', $this->order))->assertOk()->getContent();

    expect($html)->toMatch('/\.results-table th\s*\{[^}]*background:\s*#fafafa/s')
        ->and($html)->not->toMatch('/\.results-table[^\{]*\{[^}]*var\(--lab-report-accent\)/s')
        ->and($html)->toContain('.result-abnormal { font-weight: 700; }')
        ->and($html)->toContain('color: #c2410c');
});

it('keeps structural size metrics identical to pre-accent chrome (pure-color lock)', function () {
    $html = $this->get(route('investigation-orders.report', $this->order))->assertOk()->getContent();

    expect($html)->toMatch('/\.header\s*\{[^}]*border-bottom:\s*2px solid/s')
        ->and($html)->toMatch('/\.header\s*\{[^}]*padding-bottom:\s*15px/s')
        ->and($html)->toMatch('/\.header\s*\{[^}]*margin-bottom:\s*20px/s')
        ->and($html)->toMatch('/\.test-panel-header\s*\{[^}]*padding:\s*6px 8px/s')
        ->and($html)->toMatch('/\.test-panel-header\s*\{[^}]*font-size:\s*10\.5pt/s')
        ->and($html)->toMatch('/\.patient-box\s*\{[^}]*padding:\s*8px 10px/s')
        ->and($html)->toMatch('/\.patient-box\s*\{[^}]*border:\s*1px solid/s')
        ->and($html)->toMatch('/\.test-panel\s*\{[^}]*border:\s*1px solid/s')
        ->and($html)->toMatch('/\.comments-box\s*\{[^}]*padding:\s*8px 10px/s')
        ->and($html)->toMatch('/\.reviewer-block\s*\{[^}]*padding:\s*8px 10px/s')
        ->and($html)->toMatch('/\.signature-line\s*\{[^}]*border-top:\s*1px solid/s')
        ->and($html)->toMatch('/\.signature-line\s*\{[^}]*padding-top:\s*4px/s')
        ->and($html)->toMatch('/\.signature-line\s*\{[^}]*margin-top:\s*42px/s')
        ->and($html)->toMatch('/\.results-table th,\s*\.results-table td\s*\{[^}]*padding:\s*5px 8px/s')
        ->and($html)->toMatch('/\.results-table th,\s*\.results-table td\s*\{[^}]*font-size:\s*9\.5pt/s');
});

it('honors ?accent= override without persisting settings', function () {
    LabReportPrintSettings::put([
        ...LabReportPrintSettings::DEFAULTS,
        'accent_color' => '#0F766E',
    ]);

    $html = $this->get(route('investigation-orders.report', $this->order).'?accent=%23ABCDEF')
        ->assertOk()
        ->getContent();

    expect($html)->toContain('--lab-report-accent: #ABCDEF')
        ->and(LabReportPrintSettings::get()['accent_color'])->toBe('#0F766E');
});
