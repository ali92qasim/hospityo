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

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Lab Tech',
        'email' => 'lab-report@example.com',
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
        'doctor_no' => 'DOC-LAB',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005556666',
        'email' => 'doctor@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => Department::create(['name' => 'Lab', 'code' => 'LAB', 'status' => 'active'])->id,
    ]);

    $this->visit = Visit::create([
        'visit_no' => 'VIS-LAB-001',
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

function createLabTestWithParams(string $name, int $paramCount): LabTest
{
    $labTest = LabTest::create([
        'code' => strtoupper(substr(str_replace(' ', '', $name), 0, 6)),
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

function createResultForLabTest(LabOrder $order, LabTest $labTest, User $user): LabResult
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

it('groups multiple small tests onto the first page', function () {
    $uricAcid = createLabTestWithParams('Uric Acid', 1);
    $bloodSugar = createLabTestWithParams('Blood Sugar', 1);

    createResultForLabTest($this->order, $uricAcid, $this->user);
    createResultForLabTest($this->order, $bloodSugar, $this->user);

    $report = LabReportBuilder::build($this->order->fresh(['items.labTest']));

    // Each 1-param section costs 4 (header 2 + rows 1 + footer 1). Budget 12 fits both.
    expect(LabReportBuilder::FIRST_PAGE_ROW_BUDGET)->toBe(12)
        ->and($report['pages'])->toHaveCount(1)
        ->and($report['pages'][0]['sections'])->toHaveCount(2)
        ->and($report['pages'][0]['row_cost'])->toBe(8)
        ->and(collect($report['pages'][0]['sections'])->pluck('investigation.name')->all())
        ->toBe(['Blood Sugar', 'Uric Acid']);
});

it('fits three small sections on the first page under the first-page budget of 12', function () {
    expect(LabReportBuilder::FIRST_PAGE_ROW_BUDGET)->toBe(12);

    $alpha = createLabTestWithParams('Alpha Panel', 1);
    $beta = createLabTestWithParams('Beta Panel', 1);
    $gamma = createLabTestWithParams('Gamma Panel', 1);

    createResultForLabTest($this->order, $alpha, $this->user);
    createResultForLabTest($this->order, $beta, $this->user);
    createResultForLabTest($this->order, $gamma, $this->user);

    $report = LabReportBuilder::build($this->order->fresh(['items.labTest']));

    expect($report['pages'])->toHaveCount(1)
        ->and($report['pages'][0]['sections'])->toHaveCount(3)
        ->and($report['pages'][0]['row_cost'])->toBe(12)
        ->and(collect($report['pages'][0]['sections'])->pluck('investigation.name')->all())
        ->toBe(['Alpha Panel', 'Beta Panel', 'Gamma Panel']);
});

it('spills a fourth small section onto a continuation page under the first-page budget of 12', function () {
    expect(LabReportBuilder::FIRST_PAGE_ROW_BUDGET)->toBe(12);

    foreach (['A Panel', 'B Panel', 'C Panel', 'D Panel'] as $name) {
        createResultForLabTest($this->order, createLabTestWithParams($name, 1), $this->user);
    }

    $report = LabReportBuilder::build($this->order->fresh(['items.labTest']));

    expect($report['pages'])->toHaveCount(2)
        ->and($report['pages'][0]['sections'])->toHaveCount(3)
        ->and($report['pages'][0]['row_cost'])->toBe(12)
        ->and(collect($report['pages'][0]['sections'])->pluck('investigation.name')->all())
        ->toBe(['A Panel', 'B Panel', 'C Panel'])
        ->and($report['pages'][1]['sections'])->toHaveCount(1)
        ->and($report['pages'][1]['sections'][0]['investigation']->name)->toBe('D Panel');
});

it('keeps oversized tests on their own continuation page', function () {
    $cbc = createLabTestWithParams('CBC', 16);
    $uricAcid = createLabTestWithParams('Uric Acid', 1);

    createResultForLabTest($this->order, $cbc, $this->user);
    createResultForLabTest($this->order, $uricAcid, $this->user);

    $report = LabReportBuilder::build($this->order->fresh(['items.labTest']));

    expect($report['pages'])->toHaveCount(2)
        ->and($report['pages'][0]['sections'])->toHaveCount(1)
        ->and($report['pages'][0]['sections'][0]['investigation']->name)->toBe('Uric Acid')
        ->and($report['pages'][1]['sections'])->toHaveCount(1)
        ->and($report['pages'][1]['sections'][0]['investigation']->name)->toBe('CBC');
});

it('builds one section per investigation across multiple stored results', function () {
    $uricAcid = createLabTestWithParams('Uric Acid', 1);
    $bloodSugar = createLabTestWithParams('Blood Sugar', 1);

    createResultForLabTest($this->order, $uricAcid, $this->user);
    createResultForLabTest($this->order, $bloodSugar, $this->user);

    $sections = LabReportBuilder::buildSections(
        $this->order->fresh(['items.labTest']),
        LabResult::where('lab_order_id', $this->order->id)->with(['resultItems.parameter.labTest'])->get()
    );

    expect($sections)->toHaveCount(2)
        ->and(collect($sections)->pluck('investigation.name')->all())->toBe(['Blood Sugar', 'Uric Acid']);
});

/** An in-memory section with $itemCount plain parameter rows (no previous values). */
function budgetTestSection(string $name, int $itemCount): array
{
    return LabReportBuilder::makeSection(
        new LabTest(['name' => $name]),
        array_map(fn () => (object) ['previous_values' => []], range(1, $itemCount))
    );
}

it('caps continuation pages at a row budget of 26 so banded chrome fits real A4', function () {
    expect(LabReportBuilder::PAGE_ROW_BUDGET)->toBe(26);

    // Page 1: three cost-4 sections (12). Then seven cost-4 sections: 6 fill 24 of 26, the 7th spills.
    $sections = array_map(fn (int $i) => budgetTestSection("Panel {$i}", 1), range(1, 10));

    $pages = LabReportBuilder::packIntoPages($sections);

    expect(array_column($pages, 'row_cost'))->toBe([12, 24, 4])
        ->and(array_map(fn ($page) => count($page['sections']), $pages))->toBe([3, 6, 1]);
});

it('fills a continuation page to exactly the budget of 26 and spills the next section', function () {
    $sections = [
        budgetTestSection('First A', 1),
        budgetTestSection('First B', 1),
        budgetTestSection('First C', 1),
        budgetTestSection('Wide', 19),   // 2 + 19 + 1 = 22
        budgetTestSection('Small A', 1), // 22 + 4 = 26, fits exactly
        budgetTestSection('Small B', 1), // 30 > 26, spills
    ];

    $pages = LabReportBuilder::packIntoPages($sections);

    expect(array_column($pages, 'row_cost'))->toBe([12, 26, 4])
        ->and(collect($pages[1]['sections'])->pluck('investigation.name')->all())->toBe(['Wide', 'Small A'])
        ->and($pages[2]['sections'][0]['investigation']->name)->toBe('Small B');
});

it('treats a section as large only when its cost exceeds the continuation budget of 26', function () {
    $atBudget = budgetTestSection('At Budget', 23);   // 2 + 23 + 1 = 26
    $overBudget = budgetTestSection('Over Budget', 24); // 2 + 24 + 1 = 27

    expect($atBudget['row_cost'])->toBe(26)
        ->and($atBudget['is_large'])->toBeFalse()
        ->and($overBudget['row_cost'])->toBe(27)
        ->and($overBudget['is_large'])->toBeTrue();

    $pages = LabReportBuilder::packIntoPages([
        budgetTestSection('First A', 1),
        budgetTestSection('First B', 1),
        budgetTestSection('First C', 1),
        budgetTestSection('Before', 1),
        $overBudget,
        budgetTestSection('After', 1),
    ]);

    // The over-budget section closes the open continuation page and takes a page of its own.
    expect(array_column($pages, 'row_cost'))->toBe([12, 4, 27, 4])
        ->and($pages[2]['sections'][0]['investigation']->name)->toBe('Over Budget');
});
