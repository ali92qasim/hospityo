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
use App\Support\LabReportPrintSettings;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Prev Values Tech',
        'email' => 'lab-prev-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->patient = Patient::create([
        'name' => 'Prev Patient',
        'gender' => 'female',
        'age' => 30,
        'phone' => '03001112222',
        'emergency_name' => 'John Patient',
        'emergency_phone' => '03003334444',
        'emergency_relation' => 'Spouse',
    ]);

    $this->doctor = Doctor::create([
        'name' => 'Dr. Prev Ref',
        'doctor_no' => 'DOC-PREV-'.uniqid(),
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005556666',
        'email' => 'doctor-prev-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => Department::create([
            'name' => 'Lab Prev',
            'code' => 'LP'.uniqid(),
            'status' => 'active',
        ])->id,
    ]);

    $this->visit = Visit::create([
        'visit_no' => 'VIS-PREV-'.uniqid(),
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'department_id' => $this->doctor->department_id,
        'visit_type' => 'opd',
        'status' => 'active',
        'visit_datetime' => now(),
    ]);

    $this->order = LabOrder::create([
        'patient_id' => $this->patient->id,
        'visit_id' => $this->visit->id,
        'doctor_id' => $this->doctor->id,
        'priority' => 'routine',
        'status' => 'reported',
        'ordered_at' => now(),
        'sample_collected_at' => now(),
        'completed_at' => now(),
    ]);
});

function prevValuesCreateLabTestWithParams(string $name, int $paramCount): LabTest
{
    $labTest = LabTest::create([
        'code' => strtoupper(substr(str_replace(' ', '', $name), 0, 6)).substr(uniqid(), -3),
        'name' => $name,
        'category' => 'biochemistry',
        'sample_type' => 'blood',
        'price' => 500,
        'is_active' => true,
    ]);

    for ($i = 1; $i <= $paramCount; $i++) {
        LabTestParameter::create([
            'lab_test_id' => $labTest->id,
            'parameter_name' => "{$name} Param {$i}",
            'unit' => 'mg/dL',
            'data_type' => 'numeric',
            'reference_ranges' => ['normal' => '1-10'],
            'display_order' => $i,
            'is_active' => true,
        ]);
    }

    return $labTest->load('parameters');
}

function prevValuesCreateResultForLabTest(LabOrder $order, LabTest $labTest, User $user): LabResult
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
        'tested_at' => now(),
        'reported_at' => now(),
    ]);

    foreach ($labTest->parameters as $index => $parameter) {
        LabResultItem::create([
            'lab_result_id' => $result->id,
            'lab_test_parameter_id' => $parameter->id,
            'value' => (string) ($index + 1),
            'unit' => $parameter->unit,
            'flag' => 'N',
            'entered_by' => $user->id,
            'entered_at' => now(),
        ]);
    }

    return $result;
}

function makePriorOrderForPatient(Patient $patient, Visit $visit, Doctor $doctor): LabOrder
{
    return LabOrder::create([
        'patient_id' => $patient->id,
        'visit_id' => $visit->id,
        'doctor_id' => $doctor->id,
        'priority' => 'routine',
        'status' => 'reported',
        'ordered_at' => now()->subDays(7),
        'sample_collected_at' => now()->subDays(7),
        'completed_at' => now()->subDays(7),
    ]);
}

it('defaults previous_values_count to 3 with no settings row', function () {
    expect(LabReportPrintSettings::get()['previous_values_count'])->toBe(3);
});

it('attaches empty previous_values when the patient has no prior history', function () {
    $test = prevValuesCreateLabTestWithParams('Uric Acid', 1);
    prevValuesCreateResultForLabTest($this->order, $test, $this->user);

    $report = LabReportBuilder::build($this->order->fresh(['items.labTest']));
    $item = $report['pages'][0]['sections'][0]['items'][0];

    expect($item->previous_values)->toBeArray()->toBeEmpty()
        ->and($report['pages'][0]['row_cost'])->toBe(4);
});

it('loads previous values for the same parameter newest-first capped at N', function () {
    $test = prevValuesCreateLabTestWithParams('Glucose', 1);

    $older = makePriorOrderForPatient($this->patient, $this->visit, $this->doctor);
    $olderResult = prevValuesCreateResultForLabTest($older, $test, $this->user);
    $olderResult->update(['status' => 'final', 'tested_at' => now()->subDays(10)]);
    LabResultItem::where('lab_result_id', $olderResult->id)->update(['value' => '90']);

    $mid = makePriorOrderForPatient($this->patient, $this->visit, $this->doctor);
    $midResult = prevValuesCreateResultForLabTest($mid, $test, $this->user);
    $midResult->update(['status' => 'reported', 'tested_at' => now()->subDays(3)]);
    LabResultItem::where('lab_result_id', $midResult->id)->update(['value' => '95']);

    $prelimOrder = makePriorOrderForPatient($this->patient, $this->visit, $this->doctor);
    $prelim = prevValuesCreateResultForLabTest($prelimOrder, $test, $this->user);
    $prelim->update(['status' => 'preliminary', 'tested_at' => now()->subDay()]);

    prevValuesCreateResultForLabTest($this->order, $test, $this->user);

    $report = LabReportBuilder::build($this->order->fresh(['items.labTest']));
    $priors = $report['pages'][0]['sections'][0]['items'][0]->previous_values;

    expect($priors)->toHaveCount(2)
        ->and($priors[0]['value'])->toBe('95')
        ->and($priors[1]['value'])->toBe('90');
});

it('excludes results from the current order even when older LabResult rows exist on it', function () {
    $test = prevValuesCreateLabTestWithParams('Hb', 1);
    prevValuesCreateResultForLabTest($this->order, $test, $this->user);
    $first = LabResult::where('lab_order_id', $this->order->id)->first();
    $first->update(['tested_at' => now()->subDays(2), 'status' => 'final']);

    $priorOrder = makePriorOrderForPatient($this->patient, $this->visit, $this->doctor);
    $prior = prevValuesCreateResultForLabTest($priorOrder, $test, $this->user);
    $prior->update(['status' => 'final', 'tested_at' => now()->subDays(5)]);
    LabResultItem::where('lab_result_id', $prior->id)->update(['value' => '11.0']);

    $report = LabReportBuilder::build($this->order->fresh(['items.labTest']));
    $priors = collect($report['pages'][0]['sections'][0]['items'][0]->previous_values);

    expect($priors)->toHaveCount(1)
        ->and($priors->pluck('value')->all())->toBe(['11.0']);
});

it('issues a single history query regardless of parameter count (no N+1)', function () {
    $t1 = prevValuesCreateLabTestWithParams('Panel A', 3);
    $t2 = prevValuesCreateLabTestWithParams('Panel B', 2);

    $priorOrder = makePriorOrderForPatient($this->patient, $this->visit, $this->doctor);
    prevValuesCreateResultForLabTest($priorOrder, $t1, $this->user);
    prevValuesCreateResultForLabTest($priorOrder, $t2, $this->user);

    prevValuesCreateResultForLabTest($this->order, $t1, $this->user);
    prevValuesCreateResultForLabTest($this->order, $t2, $this->user);

    DB::connection('tenant')->flushQueryLog();
    DB::connection('tenant')->enableQueryLog();

    LabReportBuilder::build($this->order->fresh(['items.labTest']));

    $historyQueries = collect(DB::connection('tenant')->getQueryLog())
        ->filter(function (array $q) {
            $sql = strtolower($q['query']);

            return str_contains($sql, 'lab_result_items')
                && str_contains($sql, 'lab_orders')
                && (str_contains($sql, 'lab_test_parameter_id') || str_contains($sql, '"lab_test_parameter_id"'));
        });

    expect($historyQueries->count())->toBe(1);
});

it('costs one extra row per attached previous value', function () {
    $test = prevValuesCreateLabTestWithParams('Sodium', 1);

    $older = makePriorOrderForPatient($this->patient, $this->visit, $this->doctor);
    $olderResult = prevValuesCreateResultForLabTest($older, $test, $this->user);
    $olderResult->update(['status' => 'final', 'tested_at' => now()->subDays(10)]);
    LabResultItem::where('lab_result_id', $olderResult->id)->update(['value' => '140']);

    $mid = makePriorOrderForPatient($this->patient, $this->visit, $this->doctor);
    $midResult = prevValuesCreateResultForLabTest($mid, $test, $this->user);
    $midResult->update(['status' => 'reported', 'tested_at' => now()->subDays(3)]);
    LabResultItem::where('lab_result_id', $midResult->id)->update(['value' => '138']);

    prevValuesCreateResultForLabTest($this->order, $test, $this->user);

    $report = LabReportBuilder::build($this->order->fresh(['items.labTest']));

    expect($report['pages'][0]['sections'][0]['row_cost'])->toBe(6)
        ->and($report['pages'][0]['sections'][0]['items'][0]->previous_values)->toHaveCount(2);
});

it('does not inflate cost when previous_values is empty', function () {
    $a = prevValuesCreateLabTestWithParams('Alpha', 1);
    $b = prevValuesCreateLabTestWithParams('Beta', 1);
    prevValuesCreateResultForLabTest($this->order, $a, $this->user);
    prevValuesCreateResultForLabTest($this->order, $b, $this->user);

    $report = LabReportBuilder::build($this->order->fresh(['items.labTest']));

    expect($report['pages'])->toHaveCount(1)
        ->and($report['pages'][0]['row_cost'])->toBe(8);
});

it('packs fewer first-page sections when priors make a section exceed remaining budget', function () {
    $alpha = prevValuesCreateLabTestWithParams('Alpha Pack', 1);
    $beta = prevValuesCreateLabTestWithParams('Beta Pack', 1);

    foreach ([10, 7, 4] as $daysAgo) {
        $priorOrder = makePriorOrderForPatient($this->patient, $this->visit, $this->doctor);
        $alphaResult = prevValuesCreateResultForLabTest($priorOrder, $alpha, $this->user);
        $alphaResult->update(['status' => 'final', 'tested_at' => now()->subDays($daysAgo)]);
        $betaResult = prevValuesCreateResultForLabTest($priorOrder, $beta, $this->user);
        $betaResult->update(['status' => 'final', 'tested_at' => now()->subDays($daysAgo)]);
    }

    prevValuesCreateResultForLabTest($this->order, $alpha, $this->user);
    prevValuesCreateResultForLabTest($this->order, $beta, $this->user);

    $report = LabReportBuilder::build($this->order->fresh(['items.labTest']));

    expect($report['pages'][0]['sections'])->toHaveCount(1)
        ->and($report['pages'][0]['sections'][0]['row_cost'])->toBe(7)
        ->and($report['pages'])->toHaveCount(2)
        ->and($report['pages'][1]['sections'])->toHaveCount(1)
        ->and($report['pages'][1]['sections'][0]['row_cost'])->toBe(7);
});

it('uses actual prior count not worst-case N when history is partial', function () {
    $test = prevValuesCreateLabTestWithParams('Partial Hist', 1);

    $priorOrder = makePriorOrderForPatient($this->patient, $this->visit, $this->doctor);
    $prior = prevValuesCreateResultForLabTest($priorOrder, $test, $this->user);
    $prior->update(['status' => 'final', 'tested_at' => now()->subDays(5)]);

    prevValuesCreateResultForLabTest($this->order, $test, $this->user);

    $report = LabReportBuilder::build($this->order->fresh(['items.labTest']));

    // N default 3 but only 1 prior → cost 2+(1+1)+1 = 5, not 2+(1+3)+1 = 7
    expect($report['pages'][0]['sections'][0]['items'][0]->previous_values)->toHaveCount(1)
        ->and($report['pages'][0]['sections'][0]['row_cost'])->toBe(5);
});

it('respects saved previous_values_count when capping priors', function () {
    LabReportPrintSettings::put([
        'show_logo' => true,
        'show_qr' => true,
        'show_hospital_address' => true,
        'show_hospital_phone' => true,
        'show_hospital_email' => true,
        'show_hospital_website' => true,
        'show_patient_band' => true,
        'show_reviewers' => true,
        'show_page_numbers' => true,
        'previous_values_count' => 1,
    ]);

    $test = prevValuesCreateLabTestWithParams('Capped N', 1);

    foreach ([10, 7, 4] as $daysAgo) {
        $priorOrder = makePriorOrderForPatient($this->patient, $this->visit, $this->doctor);
        $prior = prevValuesCreateResultForLabTest($priorOrder, $test, $this->user);
        $prior->update(['status' => 'final', 'tested_at' => now()->subDays($daysAgo)]);
    }

    prevValuesCreateResultForLabTest($this->order, $test, $this->user);

    $report = LabReportBuilder::build($this->order->fresh(['items.labTest']));

    expect($report['pages'][0]['sections'][0]['items'][0]->previous_values)->toHaveCount(1);
});
