<?php

use App\Models\Visit;
use App\Support\VisitWorkflowBackLink;
use Tests\TestCase;

uses(TestCase::class);

it('returns patients listing when origin is patients', function () {
    $visit = new Visit(['visit_type' => 'opd']);

    expect(VisitWorkflowBackLink::resolve($visit, 'patients'))->toBe([
        'url' => route('patients.index'),
        'label' => 'Patients',
    ]);
});

it('returns patients listing for emergency visits that originated from patients', function () {
    $visit = new Visit(['visit_type' => 'emergency']);

    expect(VisitWorkflowBackLink::resolve($visit, 'patients'))->toBe([
        'url' => route('patients.index'),
        'label' => 'Patients',
    ]);
});

it('falls back to typed visit list when origin is missing', function (string $visitType, string $label) {
    $visit = new Visit(['visit_type' => $visitType]);

    expect(VisitWorkflowBackLink::resolve($visit, null))->toBe([
        'url' => route('visits.index', ['visit_type' => $visitType]),
        'label' => $label,
    ]);
})->with([
    ['opd', 'OPD'],
    ['emergency', 'Emergency'],
    ['ipd', 'Admitted Patients'],
]);

it('ignores unknown origin and uses visit type', function () {
    $visit = new Visit(['visit_type' => 'opd']);

    expect(VisitWorkflowBackLink::resolve($visit, 'dashboard'))->toBe([
        'url' => route('visits.index', ['visit_type' => 'opd']),
        'label' => 'OPD',
    ]);
});
