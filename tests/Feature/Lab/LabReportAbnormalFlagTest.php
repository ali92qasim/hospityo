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
use App\Models\User;
use App\Models\Visit;
use App\Services\LabReportBuilder;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;

/*
| OQ-4: abnormal results print their stored flag letter (H, L, HH, LL, A) after the value,
| inside the existing bold result cell. The results table is otherwise locked.
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
        'name' => 'Flag Letter Tech',
        'email' => 'lab-flag-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $this->user->givePermissionTo('view lab results');
    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'Flag Patient',
        'gender' => 'female',
        'age' => 30,
        'phone' => '03001112299',
    ]);

    $this->doctor = Doctor::create([
        'name' => 'Dr. Flag Ref',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005556699',
        'email' => 'doctor-flag-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => Department::create([
            'name' => 'Lab Flag',
            'code' => 'LF'.uniqid(),
            'status' => 'active',
        ])->id,
    ]);

    $this->visit = Visit::create([
        'visit_no' => 'VIS-FLAG-'.uniqid(),
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'department_id' => $this->doctor->department_id,
        'visit_type' => 'opd',
        'status' => 'active',
        'visit_datetime' => now(),
    ]);

    $this->order = abnFlagOrder($this, 0);
});

function abnFlagOrder(object $test, int $daysAgo): LabOrder
{
    return LabOrder::create([
        'patient_id' => $test->patient->id,
        'visit_id' => $test->visit->id,
        'doctor_id' => $test->doctor->id,
        'priority' => 'routine',
        'status' => 'reported',
        'ordered_at' => now()->subDays($daysAgo),
        'sample_collected_at' => now()->subDays($daysAgo),
        'completed_at' => now()->subDays($daysAgo),
    ]);
}

function abnFlagLabTest(array $parameterNames): LabTest
{
    $labTest = LabTest::create([
        'code' => 'FLG'.substr(uniqid(), -5),
        'name' => 'Flag Panel',
        'category' => 'biochemistry',
        'sample_type' => 'blood',
        'price' => 500,
        'is_active' => true,
    ]);

    foreach (array_values($parameterNames) as $i => $name) {
        LabTestParameter::create([
            'lab_test_id' => $labTest->id,
            'parameter_name' => $name,
            'unit' => 'mg/dL',
            'data_type' => 'numeric',
            'reference_ranges' => ['normal' => '1-10'],
            'display_order' => $i + 1,
            'is_active' => true,
        ]);
    }

    return $labTest->load('parameters');
}

/**
 * Adds a final result for $labTest on $order; $rows maps parameter index => [value, flag].
 *
 * @param  array<int, array{0: string, 1: ?string}>  $rows
 */
function abnFlagResult(LabOrder $order, LabTest $labTest, User $user, array $rows): LabResult
{
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
        'tested_at' => $order->ordered_at,
        'reported_at' => $order->ordered_at,
    ]);

    foreach ($labTest->parameters->values() as $index => $parameter) {
        [$value, $flag] = $rows[$index];
        LabResultItem::create([
            'lab_result_id' => $result->id,
            'lab_test_parameter_id' => $parameter->id,
            'value' => $value,
            'unit' => $parameter->unit,
            'flag' => $flag,
            'entered_by' => $user->id,
            'entered_at' => now(),
        ]);
    }

    return $result;
}

function abnFlagReportHtml(object $test): string
{
    return $test->get(route('investigation-orders.report', $test->order->fresh()))
        ->assertOk()
        ->getContent();
}

dataset('abnormal flags', ['H', 'L', 'HH', 'LL', 'A']);

it('prints the flag letter after an abnormal value', function (string $flag) {
    $labTest = abnFlagLabTest(['Flagged Param']);
    abnFlagResult($this->order, $labTest, $this->user, [['7.9', $flag]]);

    $html = abnFlagReportHtml($this);

    expect($html)->toContain('<td class="result-abnormal">7.9&nbsp;<span class="result-flag">'.$flag.'</span></td>')
        ->and(substr_count($html, '<span class="result-flag">'))->toBe(1);
})->with('abnormal flags');

it('renders critical HH/LL distinctly from H/L', function () {
    $labTest = abnFlagLabTest(['High Param', 'Critical High Param', 'Low Param', 'Critical Low Param']);
    abnFlagResult($this->order, $labTest, $this->user, [
        ['11.0', 'H'],
        ['99.0', 'HH'],
        ['0.9', 'L'],
        ['0.1', 'LL'],
    ]);

    $html = abnFlagReportHtml($this);

    preg_match_all('/<span class="result-flag">([A-Z]+)<\/span>/', $html, $m);

    expect($m[1])->toHaveCount(4)
        ->and($m[1])->toEqualCanonicalizing(['H', 'HH', 'L', 'LL'])
        ->and(array_unique($m[1]))->toHaveCount(4)
        ->and($html)->toContain('<td class="result-abnormal">11.0&nbsp;<span class="result-flag">H</span></td>')
        ->and($html)->toContain('<td class="result-abnormal">99.0&nbsp;<span class="result-flag">HH</span></td>')
        ->and($html)->toContain('<td class="result-abnormal">0.9&nbsp;<span class="result-flag">L</span></td>')
        ->and($html)->toContain('<td class="result-abnormal">0.1&nbsp;<span class="result-flag">LL</span></td>');
});

it('prints no flag for normal or unflagged values', function () {
    $labTest = abnFlagLabTest(['Normal Param', 'Unflagged Param']);
    abnFlagResult($this->order, $labTest, $this->user, [
        ['5.0', 'N'],
        ['6.0', null],
    ]);

    $html = abnFlagReportHtml($this);

    expect($html)->not->toContain('<span class="result-flag">')
        ->and($html)->not->toContain('<td class="result-abnormal">')
        ->and($html)->toContain('<td class="">5.0</td>')
        ->and($html)->toContain('<td class="">6.0</td>');
});

it('leaves previous-value rows without flag letters', function () {
    $labTest = abnFlagLabTest(['Prior Param']);
    abnFlagResult(abnFlagOrder($this, 7), $labTest, $this->user, [['88', 'H']]);
    abnFlagResult($this->order, $labTest, $this->user, [['5.0', 'N']]);

    $html = abnFlagReportHtml($this);

    preg_match_all('/<tr class="previous-result">.*?<\/tr>/s', $html, $priorRows);

    expect($priorRows[0])->toHaveCount(1)
        ->and($priorRows[0][0])->toContain('<td class="result-abnormal-muted">88</td>')
        ->and($priorRows[0][0])->not->toContain('result-flag')
        ->and($html)->not->toContain('<span class="result-flag">');
});

it('does not change result table columns, widths, padding or the abnormal rule', function () {
    $labTest = abnFlagLabTest(['Flagged Param']);
    abnFlagResult($this->order, $labTest, $this->user, [['7.9', 'HH']]);

    $html = abnFlagReportHtml($this);

    $start = strpos($html, '<thead>') + strlen('<thead>');
    $thead = substr($html, $start, strpos($html, '</thead>', $start) - $start);

    expect($html)->toContain('.result-abnormal { font-weight: 700; }')
        ->and($html)->toContain('<th style="width: 36%;">Parameter</th>')
        ->and($html)->toContain('<th style="width: 18%;">Result</th>')
        ->and($html)->toContain('<th style="width: 14%;">Unit</th>')
        ->and($html)->toContain('<th style="width: 32%;">Reference Range</th>')
        ->and(substr_count($thead, '<th'))->toBe(4)
        ->and($html)->toMatch('/\.result-flag\s*\{[^}]*white-space:\s*nowrap/s')
        ->and($html)->not->toMatch('/\.result-flag\s*\{[^}]*(font-size|padding|margin|display|color)/s');
});

it('does not change section row costs', function () {
    $labTest = abnFlagLabTest(['A', 'B', 'C', 'D', 'E']);
    $makeItems = fn (array $flags) => array_map(function (?string $flag) {
        $item = new LabResultItem(['value' => '1.0', 'flag' => $flag]);
        $item->previous_values = [['value' => '0.5', 'flag' => $flag]];

        return $item;
    }, $flags);

    $flagged = LabReportBuilder::makeSection($labTest, $makeItems(['H', 'L', 'HH', 'LL', 'A']));
    $normal = LabReportBuilder::makeSection($labTest, $makeItems(['N', 'N', 'N', 'N', 'N']));

    expect($flagged['row_cost'])->toBe($normal['row_cost'])
        ->and($flagged['is_large'])->toBe($normal['is_large']);
});
