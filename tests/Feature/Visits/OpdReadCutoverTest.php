<?php

use App\Models\OpdVisit;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\VisitTypeDetailSyncService;
use App\Workflows\Handlers\OpdVisitHandler;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'OPD Read User',
        'email' => 'opd-read@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Permission::findOrCreate('view visits', 'web');
    $this->user->givePermissionTo(['view visits']);

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'OPD Read Patient',
        'gender' => 'female',
        'age' => 32,
        'phone' => '03009990001',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03009990002',
        'emergency_relation' => 'Spouse',
    ]);
});

it('reads queue priority from spine when read flag is off', function () {
    config(['visits.dual_write_enabled' => false, 'visits.read_from_child.opd' => false]);

    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'registered',
        'priority' => 'low',
    ]);

    OpdVisit::create(['visit_id' => $visit->id, 'queue_priority' => 'critical']);

    expect($visit->queuePriority())->toBe('low');
});

it('reads queue priority from opd child when read flag is on', function () {
    config(['visits.dual_write_enabled' => false, 'visits.read_from_child.opd' => true]);

    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'registered',
        'priority' => 'low',
    ]);

    OpdVisit::create(['visit_id' => $visit->id, 'queue_priority' => 'critical']);

    expect($visit->fresh(['opdDetails'])->queuePriority())->toBe('critical');
});

it('exposes child queue priority in opd handler workflow data', function () {
    config(['visits.dual_write_enabled' => false, 'visits.read_from_child.opd' => true]);

    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'registered',
        'priority' => 'low',
    ]);

    OpdVisit::create(['visit_id' => $visit->id, 'queue_priority' => 'high']);

    $data = (new OpdVisitHandler)->workflowData($visit->fresh(['opdDetails']));

    expect($data['queue_priority'])->toBe('high');
});

it('resolve queue priority service delegates to visit accessor', function () {
    config(['visits.dual_write_enabled' => false, 'visits.read_from_child.opd' => true]);

    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'registered',
        'priority' => 'medium',
    ]);

    OpdVisit::create(['visit_id' => $visit->id, 'queue_priority' => 'critical']);

    expect(VisitTypeDetailSyncService::resolveQueuePriority($visit->fresh(['opdDetails'])))->toBe('critical');
});
