<?php

use App\Enums\VisitStatus;
use App\Exceptions\InvalidVisitTransitionException;
use App\Models\OpdVisit;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\VisitWorkflowService;
use App\Workflows\Handlers\OpdVisitHandler;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    config([
        'visits.dual_write_enabled' => false,
        'visits.enforce_workflow_transitions' => true,
    ]);

    $this->user = User::create([
        'name' => 'Workflow User',
        'email' => 'workflow@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Permission::findOrCreate('edit visits', 'web');
    $this->user->givePermissionTo(['edit visits']);

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'Workflow Patient',
        'gender' => 'male',
        'age' => 42,
        'phone' => '03009991111',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03009992222',
        'emergency_relation' => 'Sibling',
    ]);
});

it('exposes opd workflow steps from handler', function () {
    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => VisitStatus::Registered->value,
    ]);

    OpdVisit::create(['visit_id' => $visit->id, 'queue_priority' => 'medium']);

    $steps = VisitWorkflowService::for($visit)->steps();

    expect($steps)->toBe((new OpdVisitHandler)->workflowSteps())
        ->and($steps)->toHaveKey('registered', 'Registration');
});

it('allows valid opd transition when enforcement is enabled', function () {
    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => VisitStatus::Registered->value,
    ]);

    OpdVisit::create(['visit_id' => $visit->id, 'queue_priority' => 'medium']);

    VisitWorkflowService::for($visit)->transition($visit, VisitStatus::VitalsRecorded);

    expect($visit->fresh()->status)->toBe(VisitStatus::VitalsRecorded->value);
});

it('blocks invalid opd transition when enforcement is enabled', function () {
    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => VisitStatus::Registered->value,
    ]);

    OpdVisit::create(['visit_id' => $visit->id, 'queue_priority' => 'medium']);

    expect(fn () => VisitWorkflowService::for($visit)->transition($visit, VisitStatus::Completed))
        ->toThrow(InvalidVisitTransitionException::class);
});

it('allows any transition when enforcement is disabled', function () {
    config(['visits.enforce_workflow_transitions' => false]);

    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => VisitStatus::Registered->value,
    ]);

    OpdVisit::create(['visit_id' => $visit->id, 'queue_priority' => 'medium']);

    VisitWorkflowService::for($visit)->transition($visit, VisitStatus::Completed);

    expect($visit->fresh()->status)->toBe(VisitStatus::Completed->value);
});

it('records vitals through workflow transition', function () {
    config(['visits.enforce_workflow_transitions' => false]);

    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => VisitStatus::Registered->value,
    ]);

    OpdVisit::create(['visit_id' => $visit->id, 'queue_priority' => 'medium']);

    $this->post(route('visits.vitals', $visit), [
        'temperature' => 98.6,
        'pulse_rate' => 72,
    ])->assertRedirect()->assertSessionHas('success');

    expect($visit->fresh()->status)->toBe(VisitStatus::VitalsRecorded->value);
});
