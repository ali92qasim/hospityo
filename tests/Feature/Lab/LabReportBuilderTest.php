<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Investigation;
use App\Models\InvestigationOrder;
use App\Models\InvestigationOrderItem;
use App\Models\LabResult;
use App\Models\LabResultItem;
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

    $this->order = InvestigationOrder::create([
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

function createInvestigationWithParams(string $name, int $paramCount): Investigation
{
    $investigation = Investigation::create([
        'code' => strtoupper(substr(str_replace(' ', '', $name), 0, 6)),
        'name' => $name,
        'category' => 'biochemistry',
        'sample_type' => 'blood',
        'price' => 500,
        'is_active' => true,
    ]);

    for ($i = 1; $i <= $paramCount; $i++) {
        LabTestParameter::create([
            'lab_test_id' => $investigation->id,
            'parameter_name' => "{$name} Param {$i}",
            'unit' => 'mg/dL',
            'data_type' => 'numeric',
            'reference_ranges' => ['normal' => '1-10'],
            'display_order' => $i,
            'is_active' => true,
        ]);
    }

    return $investigation->load('parameters');
}

function createResultForInvestigation(InvestigationOrder $order, Investigation $investigation, User $user): LabResult
{
    $item = InvestigationOrderItem::create([
        'investigation_order_id' => $order->id,
        'investigation_id' => $investigation->id,
        'quantity' => 1,
        'priority' => 'routine',
        'status' => 'reported',
        'test_location' => 'indoor',
    ]);

    $result = LabResult::create([
        'investigation_order_id' => $order->id,
        'results' => [],
        'status' => 'final',
        'technician_id' => $user->id,
        'tested_at' => now(),
        'reported_at' => now(),
    ]);

    foreach ($investigation->parameters as $index => $parameter) {
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
    $uricAcid = createInvestigationWithParams('Uric Acid', 1);
    $bloodSugar = createInvestigationWithParams('Blood Sugar', 2);

    createResultForInvestigation($this->order, $uricAcid, $this->user);
    createResultForInvestigation($this->order, $bloodSugar, $this->user);

    $report = LabReportBuilder::build($this->order->fresh(['items.investigation']));

    expect($report['pages'])->toHaveCount(1)
        ->and($report['pages'][0]['sections'])->toHaveCount(2)
        ->and(collect($report['pages'][0]['sections'])->pluck('investigation.name')->all())
        ->toBe(['Blood Sugar', 'Uric Acid']);
});

it('keeps oversized tests on their own continuation page', function () {
    $cbc = createInvestigationWithParams('CBC', 16);
    $uricAcid = createInvestigationWithParams('Uric Acid', 1);

    createResultForInvestigation($this->order, $cbc, $this->user);
    createResultForInvestigation($this->order, $uricAcid, $this->user);

    $report = LabReportBuilder::build($this->order->fresh(['items.investigation']));

    expect($report['pages'])->toHaveCount(2)
        ->and($report['pages'][0]['sections'])->toHaveCount(1)
        ->and($report['pages'][0]['sections'][0]['investigation']->name)->toBe('Uric Acid')
        ->and($report['pages'][1]['sections'])->toHaveCount(1)
        ->and($report['pages'][1]['sections'][0]['investigation']->name)->toBe('CBC');
});

it('builds one section per investigation across multiple stored results', function () {
    $uricAcid = createInvestigationWithParams('Uric Acid', 1);
    $bloodSugar = createInvestigationWithParams('Blood Sugar', 1);

    createResultForInvestigation($this->order, $uricAcid, $this->user);
    createResultForInvestigation($this->order, $bloodSugar, $this->user);

    $sections = LabReportBuilder::buildSections(
        $this->order->fresh(['items.investigation']),
        LabResult::where('investigation_order_id', $this->order->id)->with(['resultItems.parameter.investigation'])->get()
    );

    expect($sections)->toHaveCount(2)
        ->and(collect($sections)->pluck('investigation.name')->all())->toBe(['Blood Sugar', 'Uric Acid']);
});
