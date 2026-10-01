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
        'name' => 'Banded Chrome Printer',
        'email' => 'lab-banded-'.uniqid().'@example.com',
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

function bandedChromeLabTestWithResult(LabOrder $order, User $user, string $code, string $name): void
{
    $labTest = LabTest::create([
        'code' => $code,
        'name' => $name,
        'category' => 'biochemistry',
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

function bandedChromeReportHtml(object $test): string
{
    Cache::flush();

    return $test->get(route('investigation-orders.report', $test->order->fresh()))
        ->assertOk()
        ->getContent();
}

/** Slice of $html from the band's opening tag to its closing </header>. */
function bandedChromeBandMarkup(string $html): string
{
    $start = strpos($html, '<header class="report-band"');
    expect($start)->not->toBeFalse();
    $end = strpos($html, '</header>', $start);

    return substr($html, $start, $end - $start);
}

it('renders a full-width accent header band with white text on page 1 only', function () {
    bandedChromeLabTestWithResult($this->order, $this->user, 'ALP', 'Alpha Panel');
    bandedChromeLabTestWithResult($this->order, $this->user, 'BET', 'Beta Panel');
    bandedChromeLabTestWithResult($this->order, $this->user, 'GAM', 'Gamma Panel');

    $html = bandedChromeReportHtml($this);

    expect($html)->toContain('Page 2 of 2')
        ->and(substr_count($html, 'class="report-band"'))->toBe(1)
        ->and($html)->toMatch('/\.report-band\s*\{[^}]*background:\s*var\(--lab-report-accent\)/s')
        ->and($html)->toMatch('/\.report-band\s*\{[^}]*color:\s*#fff/s')
        ->and($html)->toMatch('/\.report-band\s*\{[^}]*width:\s*100%/s')
        ->and($html)->toMatch('/\.report-band\s*\{[^}]*-webkit-print-color-adjust:\s*exact/s')
        ->and($html)->toMatch('/\.report-band\s*\{[^}]*[^-]print-color-adjust:\s*exact/s')
        ->and($html)->not->toContain('class="header')
        ->and($html)->not->toContain('class="report-title"')
        ->and($html)->not->toContain('class="hospital-name"')
        ->and(bandedChromeBandMarkup($html))->toContain('Accent City Hospital')
        ->and(bandedChromeBandMarkup($html))->toContain('class="band-report-caption">LAB REPORT<');
});

it('puts the logo on a white rounded tile no larger than 64px', function () {
    Setting::set('hospital_logo', 'logos/x.png');

    $html = bandedChromeReportHtml($this);

    expect(bandedChromeBandMarkup($html))->toContain('class="report-logo-tile"')
        ->and(bandedChromeBandMarkup($html))->toContain('logos/x.png')
        ->and($html)->toMatch('/\.report-logo-tile\s*\{[^}]*background:\s*#fff/s')
        ->and($html)->toMatch('/\.report-logo-tile\s*\{[^}]*border-radius:/s')
        ->and($html)->toMatch('/\.report-logo-tile\s*\{[^}]*-webkit-print-color-adjust:\s*exact/s')
        ->and($html)->toMatch('/\.report-logo-tile\s*\{[^}]*[^-]print-color-adjust:\s*exact/s')
        ->and($html)->toMatch('/\.report-logo-tile img\s*\{[^}]*width:\s*64px[^}]*height:\s*64px/s');

    LabReportPrintSettings::put([...LabReportPrintSettings::DEFAULTS, 'show_logo' => false]);

    expect(bandedChromeReportHtml($this))->not->toContain('class="report-logo-tile"');
});

it('shows phone, email and website as icon lines in the band, each behind its header toggle', function () {
    Setting::set('hospital_phone', '555-0199');
    Setting::set('hospital_email', 'header@chrome.test');
    Setting::set('hospital_website', 'https://header-only.chrome.test');

    $band = bandedChromeBandMarkup(bandedChromeReportHtml($this));

    expect($band)->toContain('class="report-band-contact"')
        ->and($band)->toContain('555-0199')
        ->and($band)->toContain('header@chrome.test')
        ->and($band)->toContain('https://header-only.chrome.test')
        ->and(substr_count($band, '<svg class="band-icon"'))->toBe(3)
        ->and($band)->not->toContain('Phone:')
        ->and($band)->not->toContain('Email:')
        ->and($band)->not->toContain('Website:');

    LabReportPrintSettings::put([...LabReportPrintSettings::DEFAULTS, 'show_hospital_email' => false]);

    $html = bandedChromeReportHtml($this);
    $band = bandedChromeBandMarkup($html);

    expect($band)->toContain('555-0199')
        ->and($band)->toContain('https://header-only.chrome.test')
        ->and(substr_count($band, '<svg class="band-icon"'))->toBe(2)
        ->and($html)->not->toContain('header@chrome.test');
});

it('prints the PHC registration number above the band only when filled and toggled on', function () {
    Setting::set('phc_registration_number', 'PHC-R-42');

    $on = bandedChromeReportHtml($this);

    expect($on)->toContain('class="report-reg-line"')
        ->and($on)->toContain('PHC Reg. No. PHC-R-42')
        ->and($on)->toMatch('/\.report-reg-line\s*\{[^}]*font-size:\s*8pt/s')
        ->and(strpos($on, 'class="report-reg-line"'))->toBeLessThan(strpos($on, 'class="report-band"'));

    LabReportPrintSettings::put([...LabReportPrintSettings::DEFAULTS, 'show_phc_registration' => false]);
    expect(bandedChromeReportHtml($this))->not->toContain('class="report-reg-line"');

    LabReportPrintSettings::put(LabReportPrintSettings::DEFAULTS);
    Setting::set('phc_registration_number', '   ');
    expect(bandedChromeReportHtml($this))->not->toContain('class="report-reg-line"');

    Setting::set('phc_registration_number', null);
    expect(bandedChromeReportHtml($this))->not->toContain('class="report-reg-line"');
});

it('escapes the PHC registration number', function () {
    Setting::set('phc_registration_number', '<b>PHC</b>');

    expect(bandedChromeReportHtml($this))->toContain('PHC Reg. No. &lt;b&gt;PHC&lt;/b&gt;')
        ->not->toContain('<b>PHC</b>');
});

it('keeps the address in the band behind show_hospital_address', function () {
    expect(bandedChromeBandMarkup(bandedChromeReportHtml($this)))
        ->toContain('class="band-hospital-address">11 Accent Avenue<');

    LabReportPrintSettings::put([...LabReportPrintSettings::DEFAULTS, 'show_hospital_address' => false]);

    // Registration Location in the patient band still carries the address by design; only the band drops it.
    $band = bandedChromeBandMarkup(bandedChromeReportHtml($this));
    expect($band)->not->toContain('11 Accent Avenue')
        ->and($band)->not->toContain('band-hospital-address');
});

it('renders the QR inside the band only when the patient band is off (OQ-6), exactly once', function () {
    $on = bandedChromeReportHtml($this);

    expect(substr_count($on, 'data-qr-url='))->toBe(1)
        ->and(bandedChromeBandMarkup($on))->not->toContain('report-band-qr');

    LabReportPrintSettings::put([...LabReportPrintSettings::DEFAULTS, 'show_patient_band' => false]);
    $off = bandedChromeReportHtml($this);

    expect(substr_count($off, 'data-qr-url='))->toBe(1)
        ->and(bandedChromeBandMarkup($off))->toContain('class="report-band-qr report-qr"')
        ->and($off)->toMatch('/\.report-band-qr\s*\{[^}]*background:\s*#fff[^}]*-webkit-print-color-adjust:\s*exact[^}]*[^-]print-color-adjust:\s*exact/s');

    LabReportPrintSettings::put([...LabReportPrintSettings::DEFAULTS, 'show_patient_band' => false, 'show_qr' => false]);
    expect(bandedChromeReportHtml($this))->not->toContain('data-qr-url=');

    LabReportPrintSettings::put([...LabReportPrintSettings::DEFAULTS, 'show_qr' => false]);
    expect(bandedChromeReportHtml($this))->not->toContain('data-qr-url=');
});
