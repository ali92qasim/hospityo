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
        'name' => 'Chrome Printer',
        'email' => 'lab-chrome-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $this->user->givePermissionTo('view lab results');
    $this->actingAs($this->user);

    Setting::set('hospital_name', 'Chrome City Hospital');
    Setting::set('hospital_address', '99 Lab Avenue');

    $this->patient = Patient::create([
        'name' => 'Chrome Patient',
        'gender' => 'female',
        'age' => 28,
        'phone' => '03009998877',
    ]);

    $department = Department::create([
        'name' => 'Chrome Dept',
        'code' => 'CHR'.uniqid(),
        'status' => 'active',
    ]);

    $this->doctor = Doctor::create([
        'name' => 'Dr Ordering Chrome',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005551111',
        'email' => 'ordering-chrome-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 4,
        'consultation_fee' => 900,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $this->reviewer = Doctor::create([
        'name' => 'Dr Review Chrome',
        'specialization' => 'Pathology',
        'qualification' => 'FCPS',
        'phone' => '03005552222',
        'email' => 'review-chrome-'.uniqid().'@example.com',
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
        'visit_no' => 'VIS-CHR-001',
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
        'clinical_notes' => 'Suspected anemia with fatigue; please correlate with CBC findings carefully.',
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

it('shows registration location from hospital info and not visit_type', function () {
    $this->get(route('investigation-orders.report', $this->order))
        ->assertOk()
        ->assertSee('Registration Location:', false)
        ->assertSee('Chrome City Hospital, 99 Lab Avenue', false)
        ->assertSee('Patient No.:', false)
        ->assertSee($this->order->patient->patient_no, false)
        ->assertDontSee('Case #:', false)
        ->assertDontSee('Order #:', false)
        ->assertSee('Suspected anemia with fatigue', false)
        ->assertSee('Hematology', false)
        ->assertSee('Dr Review Chrome', false)
        ->assertSee('Registration Date:', false)
        ->assertDontSee('<span class="patient-label">Registration Location:</span>
                            <span>opd</span>', false)
        ->assertDontSee('<span class="patient-label">Registration Location:</span>
                            <span>OPD</span>', false);
});

it('omits consultant, note, registration date, and verified-by when empty', function () {
    $this->labResult->reviewers()->sync([]);
    $this->labResult->update(['pathologist_id' => null]);
    $this->order->update(['clinical_notes' => null]);

    $report = \App\Services\LabReportBuilder::build($this->order->fresh(['items.labTest', 'patient', 'doctor', 'visit']));
    $report['patient_band']['registration_date'] = null;
    $report['primaryResult']->unsetRelation('pathologist');
    $report['primaryResult']->pathologist_id = null;

    $html = view('admin.lab.results.report', ['report' => $report])->render();

    expect($html)->not->toContain('<span class="patient-label">Consultant:</span>')
        ->and($html)->not->toContain('<span class="patient-label">Note:</span>')
        ->and($html)->not->toContain('<span class="patient-label">Registration Date:</span>')
        ->and($html)->not->toContain('Verified By')
        ->and($html)->not->toContain('Lab Technician')
        ->and($html)->not->toMatch('/Registration Date:[\s\S]{0,80}[—\-]/')
        ->and($html)->not->toMatch('/Verified By[\s\S]{0,120}[—\-]/');
});

it('shows verified by and registration date when present', function () {
    $this->get(route('investigation-orders.report', $this->order))
        ->assertOk()
        ->assertSee('Verified By', false)
        ->assertSee($this->user->name, false)
        ->assertSee('Registration Date:', false)
        ->assertDontSee('Lab Technician', false);
});

it('shows hospital website in letterhead when set and omits when empty', function () {
    Setting::set('hospital_website', 'https://www.chrome-hospital.test');

    $this->get(route('investigation-orders.report', $this->order))
        ->assertOk()
        ->assertSee('https://www.chrome-hospital.test', false);

    Setting::set('hospital_website', null);
    Cache::flush();

    $this->get(route('investigation-orders.report', $this->order))
        ->assertOk()
        ->assertDontSee('https://www.chrome-hospital.test', false)
        ->assertDontSee('Website:', false);
});

it('embeds a qr for publicReportUrl when show_qr is enabled', function () {
    $shareUrl = $this->order->publicReportUrl();

    $html = $this->get(route('investigation-orders.report', $this->order))
        ->assertOk()
        ->assertSee('data-qr-url="'.$shareUrl.'"', false)
        ->assertSee('<svg', false)
        ->getContent();

    expect($shareUrl)->toContain('/lab-report/')
        ->and($shareUrl)->toContain($this->order->ensureShareToken())
        ->and($html)->toContain('data-qr-url="'.$shareUrl.'"');

    // Scanning the encoded URL must land on the verify challenge, not the unlocked report.
    $this->get($shareUrl)
        ->assertOk()
        ->assertSee('Laboratory Report Access')
        ->assertSee('Patient Number')
        ->assertSee('Mobile Number')
        ->assertDontSee('Hemoglobin');
});

it('omits the qr when show_qr is disabled', function () {
    \App\Support\LabReportPrintSettings::put([
        ...\App\Support\LabReportPrintSettings::DEFAULTS,
        'show_qr' => false,
    ]);

    $this->get(route('investigation-orders.report', $this->order))
        ->assertOk()
        ->assertDontSee('data-qr-url=', false)
        ->assertDontSee('class="report-qr"', false);
});

function chromeLabTestWithResult(LabOrder $order, User $user, string $code, string $name, string $category = 'biochemistry'): void
{
    $labTest = LabTest::create([
        'code' => $code,
        'name' => $name,
        'category' => $category,
        'sample_type' => 'blood',
        'price' => 500,
        'is_active' => true,
    ]);

    $parameter = LabTestParameter::create([
        'lab_test_id' => $labTest->id,
        'parameter_name' => "{$name} Param",
        'unit' => 'mg/dL',
        'data_type' => 'numeric',
        'reference_ranges' => ['normal' => '1-10'],
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

    $result = LabResult::create([
        'lab_order_id' => $order->id,
        'results' => [],
        'status' => 'final',
        'technician_id' => $user->id,
        'tested_at' => now(),
        'reported_at' => now(),
    ]);

    LabResultItem::create([
        'lab_result_id' => $result->id,
        'lab_test_parameter_id' => $parameter->id,
        'value' => '1.0',
        'unit' => 'mg/dL',
        'flag' => 'N',
        'entered_by' => $user->id,
        'entered_at' => now(),
    ]);
}

it('prints page N of M across packed logical pages', function () {
    chromeLabTestWithResult($this->order, $this->user, 'ALP', 'Alpha Panel');
    chromeLabTestWithResult($this->order, $this->user, 'BET', 'Beta Panel');
    chromeLabTestWithResult($this->order, $this->user, 'GAM', 'Gamma Panel');
    // Existing CBC (1 param) + Alpha + Beta + Gamma = four cost-4 sections → page 2 under budget 12.

    $html = $this->get(route('investigation-orders.report', $this->order->fresh()))
        ->assertOk()
        ->assertSee('Page 1 of 2', false)
        ->assertSee('Page 2 of 2', false)
        ->getContent();

    expect(substr_count($html, 'Page 1 of 2'))->toBe(1)
        ->and(substr_count($html, 'Page 2 of 2'))->toBe(1);
});

it('packs three 1-param sections onto one logical first page under budget 12', function () {
    chromeLabTestWithResult($this->order, $this->user, 'ALP', 'Alpha Panel');
    chromeLabTestWithResult($this->order, $this->user, 'BET', 'Beta Panel');
    // Existing CBC + Alpha + Beta = three cost-4 sections = 12.

    $html = $this->get(route('investigation-orders.report', $this->order->fresh()))
        ->assertOk()
        ->assertSee('Page 1 of 1', false)
        ->assertDontSee('Page 2 of', false)
        ->getContent();

    expect(substr_count($html, 'class="report-page"'))->toBe(1)
        ->and(substr_count($html, 'class="test-panel"'))->toBe(3);

    file_put_contents(
        storage_path('app/lab-report-a4-budget-check.html'),
        $html
    );
});

it('renders horizontal reviewer credential blocks for each reviewing doctor', function () {
    $departmentId = $this->doctor->department_id;

    $reviewerB = Doctor::create([
        'name' => 'Second Reviewer',
        'specialization' => 'Hematology',
        'qualification' => 'MBBS, FCPS',
        'phone' => '03005553333',
        'email' => 'review-b-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 7,
        'consultation_fee' => 1400,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $departmentId,
    ]);

    LabReportRosterDoctor::create([
        'doctor_id' => $reviewerB->id,
        'sort_order' => 1,
    ]);

    $this->labResult->reviewers()->sync([
        $this->reviewer->id => ['sort_order' => 0],
        $reviewerB->id => ['sort_order' => 1],
    ]);

    Setting::set('hospital_phone', '555-0100');
    Setting::set('hospital_email', 'lab@chrome.test');
    Setting::set('hospital_website', 'https://www.chrome-hospital.test');

    \App\Support\LabReportPrintSettings::put([
        ...\App\Support\LabReportPrintSettings::DEFAULTS,
        'show_footer_phone' => true,
        'show_footer_email' => true,
        'show_footer_website' => true,
    ]);

    $html = $this->get(route('investigation-orders.report', $this->order))
        ->assertOk()
        ->assertSee('class="reviewer-blocks"', false)
        ->assertSee('class="reviewer-block"', false)
        ->assertSee('Dr. Dr Review Chrome', false)
        ->assertSee('FCPS', false)
        ->assertSee('Pathology', false)
        ->assertSee('Dr. Second Reviewer', false)
        ->assertSee('MBBS, FCPS', false)
        ->assertSee('Hematology', false)
        ->assertSee('class="report-contact"', false)
        ->assertSee('555-0100', false)
        ->assertSee('lab@chrome.test', false)
        ->assertSee('https://www.chrome-hospital.test', false)
        ->assertDontSee('Lab Technician', false)
        ->getContent();

    expect(substr_count($html, 'class="reviewer-block"'))->toBe(2)
        ->and($html)->toMatch('/\.reviewer-block\s*\{[^}]*border:\s*1px solid var\(--lab-report-accent\)/')
        ->and($html)->not->toMatch('/\.patient-item\s*\{[^}]*border:/')
        ->and($html)->not->toMatch('/\.signature-line\s*\{[^}]*border:/');
});

it('omits footer contact by default while header contact toggles still show', function () {
    Setting::set('hospital_phone', '555-0199');
    Setting::set('hospital_email', 'header@chrome.test');
    Setting::set('hospital_address', '99 Lab Avenue');
    Setting::set('hospital_website', 'https://header-only.chrome.test');

    Cache::flush();

    $html = $this->get(route('investigation-orders.report', $this->order))
        ->assertOk()
        ->assertSee('Phone: 555-0199', false)
        ->assertSee('Email: header@chrome.test', false)
        ->assertSee('Website: https://header-only.chrome.test', false)
        ->assertDontSee('class="report-contact"', false)
        ->getContent();

    expect(\App\Support\LabReportPrintSettings::get())->toMatchArray([
        'show_hospital_phone' => true,
        'show_footer_phone' => false,
        'show_footer_email' => false,
        'show_footer_address' => false,
        'show_footer_website' => false,
    ]);
});

it('omits reviewer footer blocks when there are no reviewers', function () {
    $this->labResult->reviewers()->sync([]);

    $this->get(route('investigation-orders.report', $this->order))
        ->assertOk()
        ->assertDontSee('class="reviewer-blocks"', false)
        ->assertDontSee('class="reviewer-block"', false);
});

it('still shows reviewer blocks and page numbers when previous values are present', function () {
    $parameterId = LabResultItem::where('lab_result_id', $this->labResult->id)->value('lab_test_parameter_id');

    $priorOrder = LabOrder::create([
        'patient_id' => $this->patient->id,
        'visit_id' => $this->visit->id,
        'doctor_id' => $this->doctor->id,
        'priority' => 'routine',
        'status' => 'reported',
        'ordered_at' => now()->subDays(10),
        'sample_collected_at' => now()->subDays(10),
        'completed_at' => now()->subDays(10),
    ]);

    $priorResult = LabResult::create([
        'lab_order_id' => $priorOrder->id,
        'results' => [],
        'status' => 'final',
        'technician_id' => $this->user->id,
        'tested_at' => \Illuminate\Support\Carbon::parse('2026-01-20 08:00:00'),
        'reported_at' => now()->subDays(10),
    ]);

    LabResultItem::create([
        'lab_result_id' => $priorResult->id,
        'lab_test_parameter_id' => $parameterId,
        'value' => '12.8',
        'unit' => 'g/dL',
        'flag' => 'N',
        'entered_by' => $this->user->id,
        'entered_at' => now()->subDays(10),
    ]);

    $html = $this->get(route('investigation-orders.report', $this->order->fresh()))
        ->assertOk()
        ->assertSee('<tr class="previous-result">', false)
        ->assertSee('12.8')
        ->assertSee('20 Jan 2026')
        ->assertSee('class="reviewer-blocks"', false)
        ->assertSee('Dr. Dr Review Chrome', false)
        ->assertSee('Page 1 of', false)
        ->getContent();

    expect($html)->toContain('class="reviewer-block"')
        ->and($html)->toContain('<tr class="previous-result">')
        ->and($html)->toContain('Page 1 of');
});
