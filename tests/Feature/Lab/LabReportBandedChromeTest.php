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

/** Slice of $html from the first occurrence of $start up to (not including) $end. */
function bandedChromeBetween(string $html, string $start, string $end): string
{
    $from = strpos($html, $start);
    expect($from)->not->toBeFalse("missing start anchor {$start}");
    $to = strpos($html, $end, $from);
    expect($to)->not->toBeFalse("missing end anchor {$end}");

    return substr($html, $from, $to - $from);
}

it('renders the patient strip in three columns with every confirmed field', function () {
    $html = bandedChromeReportHtml($this);
    $left = bandedChromeBetween($html, 'patient-strip-col patient-strip-left', '</div><!-- /left -->');
    $middle = bandedChromeBetween($html, 'patient-strip-col patient-strip-middle', '</div><!-- /middle -->');

    foreach (['Patient Name:', 'Age / Sex:', 'Referred By:', 'Patient No.:', 'Department:', 'Consultant:'] as $label) {
        expect($left)->toContain($label);
    }
    foreach (['Registration Location:', 'Registration Date:', 'Collection:', 'Reporting:'] as $label) {
        expect($middle)->toContain($label)
            ->and($left)->not->toContain($label);
    }
    foreach (['Patient Name:', 'Age / Sex:', 'Referred By:', 'Patient No.:', 'Department:', 'Consultant:'] as $label) {
        expect($middle)->not->toContain($label);
    }

    $note = bandedChromeBetween($html, 'class="patient-strip-note"', '</div>');

    expect($html)->toContain('class="patient-strip"')
        ->and($note)->toContain('Note:')
        ->and($left)->not->toContain('Note:')
        ->and($middle)->not->toContain('Note:')
        ->and($html)->toContain('Accent chrome fixture note.')
        ->and($html)->toContain($this->patient->fresh()->patient_no)
        ->and($html)->toContain('Accent City Hospital, 11 Accent Avenue')
        ->and($html)->not->toContain('class="patient-box"')
        ->and($html)->not->toContain('patient-grid')
        ->and($html)->toMatch('/\.patient-strip\s*\{[^}]*grid-template-columns:\s*1fr 1fr 26mm/s')
        ->and($html)->toMatch('/\.patient-strip\.no-qr\s*\{[^}]*grid-template-columns:\s*1fr 1fr;/s')
        ->and($html)->toMatch('/\.patient-strip-note\s*\{[^}]*grid-column:\s*1 \/ 3/s')
        ->and($html)->toMatch('/\.patient-item\s*\{[^}]*font-size:\s*9\.5pt/s');
});

it('places the QR in the strip right column and not in the band when the strip is shown', function () {
    $html = bandedChromeReportHtml($this);
    $strip = bandedChromeBetween($html, 'class="patient-strip"', '<!-- /patient-strip -->');

    expect(substr_count($html, 'data-qr-url='))->toBe(1)
        ->and($strip)->toContain('class="patient-strip-qr report-qr" data-qr-url=')
        ->and(bandedChromeBandMarkup($html))->not->toContain('report-band-qr')
        ->and($html)->not->toContain('.patient-box > .report-qr')
        ->and($html)->not->toContain('.patient-box::after');
});

it('relocates the QR to the band right edge when the patient band is off (OQ-6)', function () {
    LabReportPrintSettings::put([...LabReportPrintSettings::DEFAULTS, 'show_patient_band' => false]);

    $html = bandedChromeReportHtml($this);

    expect($html)->not->toContain('class="patient-strip')
        ->and($html)->not->toContain('patient-strip-qr report-qr')
        ->and(bandedChromeBandMarkup($html))->toContain('class="report-band-qr report-qr" data-qr-url=')
        ->and(substr_count($html, 'data-qr-url='))->toBe(1);
});

it('omits the QR everywhere when show_qr is off', function () {
    LabReportPrintSettings::put([...LabReportPrintSettings::DEFAULTS, 'show_qr' => false]);

    $html = bandedChromeReportHtml($this);

    expect($html)->not->toContain('data-qr-url=')
        ->and($html)->toContain('class="patient-strip no-qr"')
        ->and($html)->not->toContain('class="patient-strip-qr');
});

it('draws a 1px accent divider at the bottom of the patient strip (OQ-7)', function () {
    $html = bandedChromeReportHtml($this);

    expect($html)->toMatch('/\.patient-strip\s*\{[^}]*border-bottom:\s*1px solid var\(--lab-report-accent\)/s');
});

it('separates the band from the first panel bar when the patient strip is off', function () {
    $html = bandedChromeReportHtml($this);

    expect($html)->toMatch('/\.report-band\s*\{[^}]*margin-bottom:\s*3mm/s')
        ->and($html)->toMatch('/\.patient-strip\s*\{[^}]*padding:\s*0 0 2\.5mm/s');
});

it('omits empty optional fields exactly as the old band did', function () {
    $this->order->update(['clinical_notes' => null]);
    $this->labResult->reviewers()->sync([]);

    $html = bandedChromeReportHtml($this);
    $left = bandedChromeBetween($html, 'patient-strip-col patient-strip-left', '</div><!-- /left -->');
    $middle = bandedChromeBetween($html, 'patient-strip-col patient-strip-middle', '</div><!-- /middle -->');

    // Registration Date falls back to ordered_at (NOT NULL) and Department to the test category
    // (required enum), so both stay present by data logic; Note and Consultant can go empty.
    expect($html)->toContain('class="patient-strip"')
        ->and($html)->not->toContain('Note:')
        ->and($html)->not->toContain('class="patient-strip-note"')
        ->and($html)->not->toContain('Consultant:')
        ->and($middle)->toContain('Registration Date:')
        ->and($left)->toContain('Patient No.:')
        ->and($middle)->toContain('Collection:')
        ->and($middle)->toContain('Reporting:');
});

/** Adds enough single-parameter panels to the order to spill onto three logical pages. */
function bandedChromeThreePageOrder(object $test): void
{
    // 4 rows per one-parameter panel: page 1 (12) holds 3, page 2 (30) holds 7, page 3 takes the rest.
    foreach (range(1, 12) as $i) {
        bandedChromeLabTestWithResult($test->order, $test->user, 'RH'.$i, "Running Panel {$i}");
    }
}

it('shows a slim running header with patient identification on every continuation page only', function () {
    bandedChromeThreePageOrder($this);

    $html = bandedChromeReportHtml($this);
    $running = bandedChromeBetween($html, 'class="running-header"', '</div><!-- /running-header -->');
    $patientNo = $this->patient->fresh()->patient_no;

    expect($html)->toContain('Page 3 of 3')
        ->and(substr_count($html, 'class="running-header"'))->toBe(2)
        ->and(substr_count($html, 'class="report-band"'))->toBe(1)
        ->and(substr_count($html, '</div><!-- /running-header -->'))->toBe(2)
        ->and($running)->toContain('<strong>Accent City Hospital</strong> · LAB REPORT')
        ->and($running)->toContain('Accent Patient')
        ->and($running)->toContain('Patient No. '.$patientNo)
        ->and($running)->not->toContain($this->order->fresh()->order_number)
        ->and($running)->not->toContain('<img')
        ->and($running)->not->toContain('data-qr-url=')
        ->and(strpos($html, 'class="running-header"'))->toBeGreaterThan(strpos($html, 'Page 1 of 3'));
});

it('keeps the running header on continuation pages even with every header toggle off', function () {
    bandedChromeThreePageOrder($this);
    LabReportPrintSettings::put([
        ...LabReportPrintSettings::DEFAULTS,
        'show_logo' => false,
        'show_hospital_address' => false,
        'show_patient_band' => false,
        'show_qr' => false,
    ]);

    expect(substr_count(bandedChromeReportHtml($this), 'class="running-header"'))->toBe(2);
});

it('does not render a running header on a single-page report', function () {
    $html = bandedChromeReportHtml($this);

    expect($html)->toContain('Page 1 of 1')
        ->and($html)->not->toContain('class="running-header"');
});

it('styles the running header as a slim accent fill with forced background printing', function () {
    $html = bandedChromeReportHtml($this);

    expect($html)->toMatch('/\.running-header\s*\{[^}]*background:\s*var\(--lab-report-accent\)/s')
        ->and($html)->toMatch('/\.running-header\s*\{[^}]*color:\s*#fff/s')
        ->and($html)->toMatch('/\.running-header\s*\{[^}]*-webkit-print-color-adjust:\s*exact/s')
        ->and($html)->toMatch('/\.running-header\s*\{[^}]*[^-]print-color-adjust:\s*exact/s')
        ->and($html)->toMatch('/\.running-header\s*\{[^}]*padding:\s*2mm 4mm/s')
        ->and($html)->toMatch('/\.running-header\s*\{[^}]*margin-bottom:\s*4mm/s')
        ->and($html)->toMatch('/\.running-header\s*\{[^}]*font-size:\s*9pt/s');
});

it('widens middle-column patient labels so all four middle values share one start edge', function () {
    $html = bandedChromeReportHtml($this);

    expect($html)->toMatch('/\.patient-label\s*\{[^}]*min-width:\s*27mm/s')
        ->and($html)->toMatch('/\.patient-strip-middle \.patient-label\s*\{[^}]*min-width:\s*\d+(\.\d+)?mm/s')
        ->and($html)->toMatch('/\.patient-item\s*\{[^}]*font-size:\s*9\.5pt/s');

    preg_match('/\.patient-strip-middle \.patient-label\s*\{[^}]*min-width:\s*(\d+(?:\.\d+)?)mm/s', $html, $m);
    expect((float) $m[1])->toBeGreaterThan(27.0);
});
