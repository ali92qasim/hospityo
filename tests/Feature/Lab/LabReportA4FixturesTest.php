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
use App\Services\LabReportBuilder;
use App\Support\LabReportAccentContrast;
use App\Support\LabReportPrintSettings;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;

/*
| V1–V5 A4 real-render fixtures. Each test renders the lab report route, asserts today's
| logical packing, and writes the HTML to storage/app/lab-report-a4/ for
| scripts/lab-report-print-verify.mjs (CDP print, backgrounds off).
*/

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
        'name' => 'A4 Fixture Pathologist',
        'email' => 'lab-a4-'.uniqid().'@example.com',
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

    $this->department = Department::create([
        'name' => 'A4 Dept',
        'code' => 'A4F'.uniqid(),
        'status' => 'active',
    ]);

    $this->doctor = a4FixtureDoctor($this->department->id, 'Dr Ordering Chrome', 'General', 'MBBS');
    $this->reviewer = a4FixtureDoctor($this->department->id, 'Dr Review Chrome', 'Pathology', 'FCPS');

    LabReportRosterDoctor::create([
        'doctor_id' => $this->reviewer->id,
        'sort_order' => 0,
    ]);

    $this->visit = Visit::create([
        'visit_no' => 'VIS-A4F-001',
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'department_id' => $this->department->id,
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
});

function a4FixtureDoctor(int $departmentId, string $name, string $specialization, string $qualification): Doctor
{
    return Doctor::create([
        'name' => $name,
        'specialization' => $specialization,
        'qualification' => $qualification,
        'phone' => '0300'.random_int(1000000, 9999999),
        'email' => 'a4-doctor-'.uniqid().'@example.com',
        'gender' => 'female',
        'experience_years' => 9,
        'consultation_fee' => 1500,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $departmentId,
    ]);
}

/**
 * Creates one lab test (one report section) with its own LabResult on $order.
 *
 * @param  list<array{0: string, 1: string, 2: string, 3?: string, 4?: string}>  $params  [name, value, flag, unit, normal range]
 * @return array{result: LabResult, parameters: list<LabTestParameter>}
 */
function a4FixtureSection(LabOrder $order, User $user, string $code, string $name, array $params, string $category = 'biochemistry', array $resultAttributes = []): array
{
    $labTest = LabTest::create([
        'code' => $code,
        'name' => $name,
        'category' => $category,
        'sample_type' => 'blood',
        'price' => 500,
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
        'tested_at' => now()->subHours(2),
        'reported_at' => now()->subHour(),
        ...$resultAttributes,
    ]);

    $parameters = [];
    foreach (array_values($params) as $i => $param) {
        $unit = $param[3] ?? 'mg/dL';
        $parameter = LabTestParameter::create([
            'lab_test_id' => $labTest->id,
            'parameter_name' => $param[0],
            'unit' => $unit,
            'data_type' => 'numeric',
            'reference_ranges' => ['normal' => $param[4] ?? '1-10'],
            'display_order' => $i + 1,
            'is_active' => true,
        ]);

        LabResultItem::create([
            'lab_result_id' => $result->id,
            'lab_test_parameter_id' => $parameter->id,
            'value' => $param[1],
            'unit' => $unit,
            'flag' => $param[2],
            'entered_by' => $user->id,
            'entered_at' => now()->subHours(2),
        ]);

        $parameters[] = $parameter;
    }

    return ['result' => $result, 'parameters' => $parameters];
}

/** Creates one prior (other-order, final) result for $parameter so it shows as a previous value. */
function a4FixturePrior(LabOrder $currentOrder, User $user, LabTestParameter $parameter, string $value, string $flag, int $daysAgo): void
{
    $priorOrder = LabOrder::create([
        'patient_id' => $currentOrder->patient_id,
        'visit_id' => $currentOrder->visit_id,
        'doctor_id' => $currentOrder->doctor_id,
        'priority' => 'routine',
        'status' => 'reported',
        'ordered_at' => now()->subDays($daysAgo),
        'sample_collected_at' => now()->subDays($daysAgo),
        'completed_at' => now()->subDays($daysAgo),
    ]);

    $priorResult = LabResult::create([
        'lab_order_id' => $priorOrder->id,
        'results' => [],
        'status' => 'final',
        'technician_id' => $user->id,
        'tested_at' => now()->subDays($daysAgo),
        'reported_at' => now()->subDays($daysAgo),
    ]);

    LabResultItem::create([
        'lab_result_id' => $priorResult->id,
        'lab_test_parameter_id' => $parameter->id,
        'value' => $value,
        'unit' => $parameter->unit,
        'flag' => $flag,
        'entered_by' => $user->id,
        'entered_at' => now()->subDays($daysAgo),
    ]);
}

/** Sets every hospital-info field and turns all footer contact toggles on (merged into $overrides). */
function a4FixtureFullHospitalInfo(array $overrides = []): void
{
    $address = 'Plot 14-B, Main Boulevard, Block C, Gulberg III, Near Liberty Market Roundabout, Lahore 54660, Punjab, Islamic Republic of Pakistan (Gate 2)';
    expect(mb_strlen($address))->toBe(140);

    Setting::set('hospital_name', 'Chrome City Hospital & Diagnostic Research Centre');
    Setting::set('hospital_address', $address);
    Setting::set('hospital_phone', '+92 42 3577 1234, +92 300 1234567');
    Setting::set('hospital_email', 'laboratory.reports@chromecityhospital.test');
    Setting::set('hospital_website', 'https://www.chromecityhospital-diagnostics.test');
    Setting::set('phc_registration_number', 'PHC-R-0123456789');

    LabReportPrintSettings::put([
        ...LabReportPrintSettings::DEFAULTS,
        'show_footer_address' => true,
        'show_footer_phone' => true,
        'show_footer_email' => true,
        'show_footer_website' => true,
        'previous_values_count' => 3,
        ...$overrides,
    ]);
    Cache::flush();
}

/** Syncs two roster reviewers onto $result (the existing roster doctor + a second). */
function a4FixtureTwoReviewers(object $test, LabResult $result): void
{
    $reviewerB = a4FixtureDoctor($test->department->id, 'Second Reviewer', 'Hematology', 'MBBS, FCPS (Haematology)');
    LabReportRosterDoctor::create([
        'doctor_id' => $reviewerB->id,
        'sort_order' => 1,
    ]);

    $result->reviewers()->sync([
        $test->reviewer->id => ['sort_order' => 0],
        $reviewerB->id => ['sort_order' => 1],
    ]);
}

function a4FixtureComment(): string
{
    $comment = 'Markedly raised value noted and confirmed on repeat analysis of the same sample. '
        .'Serology titre positive at 1:320; correlate clinically with presenting symptoms and history. '
        .'Suggest repeat testing after two weeks to assess trend. Sample received in good condition, '
        .'processed within stability window.';
    expect(mb_strlen($comment))->toBeGreaterThanOrEqual(280)->toBeLessThanOrEqual(320);

    return $comment;
}

/**
 * V2 data: everything on, page-1 sections totalling exactly 12 row units including previous values.
 *   Section "Cardiac Marker Panel": 1 param (HH '1234.567') + 3 priors → 2 + (1 + 3) + 1 = 7
 *   Section "Serology Titre Panel": 1 param (A 'Positive (1:320)') + 1 prior → 2 + (1 + 1) + 1 = 5
 */
function a4FixtureWorstCase(object $test, array $settingOverrides = []): void
{
    a4FixtureFullHospitalInfo($settingOverrides);

    $test->order->update([
        'clinical_notes' => 'Known case of chronic kidney disease stage 3 on regular follow-up; presented with chest pain, '
            .'breathlessness and fever for five days; rule out acute coronary syndrome and autoimmune serology.',
    ]);
    expect(mb_strlen($test->order->clinical_notes))->toBeGreaterThan(120);

    $cardiac = a4FixtureSection($test->order, $test->user, 'CMP', 'Cardiac Marker Panel', [
        ['High Sensitivity Troponin I', '1234.567', 'HH', 'ng/L', '0-34'],
    ], 'biochemistry');
    a4FixturePrior($test->order, $test->user, $cardiac['parameters'][0], '845.210', 'HH', 3);
    a4FixturePrior($test->order, $test->user, $cardiac['parameters'][0], '312.400', 'H', 10);
    a4FixturePrior($test->order, $test->user, $cardiac['parameters'][0], '12.100', 'N', 30);

    $serology = a4FixtureSection($test->order, $test->user, 'STP', 'Serology Titre Panel', [
        ['Antinuclear Antibody (ANA) Titre', 'Positive (1:320)', 'A', 'titre', 'Negative (< 1:80)'],
    ], 'serology', [
        'comments' => a4FixtureComment(),
        'pathologist_id' => $test->user->id,
        'verified_at' => now()->subHour(),
    ]);
    a4FixturePrior($test->order, $test->user, $serology['parameters'][0], 'Positive (1:160)', 'A', 30);

    a4FixtureTwoReviewers($test, $serology['result']);

    $report = LabReportBuilder::build($test->order->fresh());
    expect($report['pages'])->toHaveCount(1)
        ->and($report['pages'][0]['row_cost'])->toBe(LabReportBuilder::FIRST_PAGE_ROW_BUDGET)
        ->and($report['pages'][0]['row_cost'])->toBe(12);
}

function a4FixtureRender(object $test, int $expectedPages, string $filename): string
{
    $html = $test->get(route('investigation-orders.report', $test->order->fresh()))
        ->assertOk()
        ->getContent();

    expect(substr_count($html, 'class="report-page"'))->toBe($expectedPages);

    $dir = storage_path('app/lab-report-a4');
    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($dir.DIRECTORY_SEPARATOR.$filename, $html);

    return $html;
}

it('writes V1: three cost-4 sections on one page (budget 12)', function () {
    $cbc = a4FixtureSection($this->order, $this->user, 'CBC', 'Complete Blood Count', [
        ['Hemoglobin', '11.2', 'L', 'g/dL', '12-16'],
    ], 'hematology', [
        'pathologist_id' => $this->user->id,
        'verified_at' => now()->subHour(),
    ]);
    $cbc['result']->reviewers()->sync([$this->reviewer->id => ['sort_order' => 0]]);
    a4FixtureSection($this->order, $this->user, 'ALP', 'Alpha Panel', [['Alpha Panel Param', '1.0', 'N']]);
    a4FixtureSection($this->order, $this->user, 'BET', 'Beta Panel', [['Beta Panel Param', '1.0', 'N']]);

    $report = LabReportBuilder::build($this->order->fresh());
    expect($report['pages'])->toHaveCount(1)
        ->and($report['pages'][0]['row_cost'])->toBe(12);

    a4FixtureRender($this, 1, 'v1-baseline.html');
});

it('writes V2: page-1 worst case at exactly 12 row units', function () {
    a4FixtureWorstCase($this);

    $html = a4FixtureRender($this, 1, 'v2-page1-worst.html');

    expect($html)->toContain('1234.567')
        ->and($html)->toContain('Positive (1:320)')
        ->and(substr_count($html, '<tr class="previous-result">'))->toBe(4)
        ->and(substr_count($html, 'class="reviewer-block"'))->toBe(2)
        ->and($html)->toContain('Verified By')
        ->and($html)->toContain('class="report-contact"')
        ->and($html)->toContain('Registration Date:');
});

it('writes V3: multi-page 12 / 30 / 30 with comments, reviewers and verified by', function () {
    a4FixtureFullHospitalInfo();

    // Page 1: three cost-4 sections = 12.
    a4FixtureSection($this->order, $this->user, 'ALP', 'Alpha Panel', [['Alpha Panel Param', '1.0', 'N']]);
    a4FixtureSection($this->order, $this->user, 'BET', 'Beta Panel', [['Beta Panel Param', '1.0', 'N']]);
    a4FixtureSection($this->order, $this->user, 'GAM', 'Gamma Panel', [['Gamma Panel Param', '1.0', 'N']]);

    // Pages 2 and 3: one 27-param section each = 2 + 27 + 1 = 30.
    $paramsFor = fn (string $prefix) => array_map(
        fn (int $i) => ["{$prefix} Parameter {$i}", (string) (10 + $i).'.5', $i % 9 === 0 ? 'H' : 'N', 'mg/dL', '1-50'],
        range(1, 27)
    );
    a4FixtureSection($this->order, $this->user, 'XTP', 'Extended Metabolic Profile', $paramsFor('Metabolic'));
    $last = a4FixtureSection($this->order, $this->user, 'YTP', 'Extended Tumour Marker Profile', $paramsFor('Marker'), 'biochemistry', [
        'comments' => a4FixtureComment(),
        'pathologist_id' => $this->user->id,
        'verified_at' => now()->subHour(),
    ]);
    a4FixtureTwoReviewers($this, $last['result']);

    $report = LabReportBuilder::build($this->order->fresh());
    expect(array_column($report['pages'], 'row_cost'))->toBe([12, 30, 30])
        ->and($report['primaryResult']->id)->toBe($last['result']->id);

    $html = a4FixtureRender($this, 3, 'v3-multipage.html');

    expect($html)->toContain('Page 3 of 3')
        ->and(substr_count($html, 'class="reviewer-block"'))->toBe(2)
        ->and($html)->toContain('Verified By')
        ->and($html)->toContain('class="comments-box"');
});

it('writes V4: V2 data with every boolean toggle off except show_qr, and an all-off variant', function () {
    $allOff = collect(LabReportPrintSettings::DEFAULTS)
        ->filter(fn ($value) => is_bool($value))
        ->map(fn () => false)
        ->all();

    a4FixtureWorstCase($this, [...$allOff, 'show_qr' => true]);

    $html = a4FixtureRender($this, 1, 'v4-toggles-off.html');
    expect($html)->toContain('class="report-qr"')
        ->and($html)->not->toContain('class="patient-box"');

    LabReportPrintSettings::put([...LabReportPrintSettings::DEFAULTS, ...$allOff]);
    Cache::flush();
    expect(collect(LabReportPrintSettings::get())->filter(fn ($v) => $v === true))->toBeEmpty();

    $html = a4FixtureRender($this, 1, 'v4-all-off.html');
    expect($html)->not->toContain('class="report-qr"')
        ->and($html)->not->toContain('class="patient-box"');
});

it('writes V5: V2 data on the default accent and on a borderline 4.5:1 accent', function () {
    $ratio = LabReportAccentContrast::ratioAgainstWhite('#30827C');
    expect($ratio)->toBeGreaterThanOrEqual(4.5)->toBeLessThan(4.7);

    a4FixtureWorstCase($this, ['accent_color' => '#0F766E']);
    $html = a4FixtureRender($this, 1, 'v5-default-accent.html');
    expect($html)->toContain('--lab-report-accent: #0F766E');

    LabReportPrintSettings::put([
        ...LabReportPrintSettings::get(),
        'accent_color' => '#30827C',
    ]);
    Cache::flush();

    $html = a4FixtureRender($this, 1, 'v5-borderline-accent.html');
    expect($html)->toContain('--lab-report-accent: #30827C');
});
