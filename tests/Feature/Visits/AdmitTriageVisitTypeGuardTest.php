<?php

use App\Models\Bed;
use App\Models\Department;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\Ward;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    Permission::findOrCreate('edit visits', 'web');
    Permission::findOrCreate('view visits', 'web');

    $this->user = User::create([
        'name' => 'Admit Triage Guard User',
        'email' => 'atg-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $this->user->givePermissionTo(['edit visits', 'view visits']);
    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'Admit Triage Patient',
        'gender' => 'female',
        'age' => 28,
        'phone' => '03001110000',
    ]);
});

function makeTypedVisit(Patient $patient, string $type): Visit
{
    return Visit::create([
        'patient_id' => $patient->id,
        'visit_type' => $type,
        'visit_datetime' => now(),
        'status' => 'registered',
        'doctor_id' => null,
    ]);
}

function makeAvailableBed(): Bed
{
    $department = Department::create([
        'name' => 'Guard Med',
        'code' => 'GMED-'.uniqid(),
        'status' => 'active',
    ]);
    $ward = Ward::create([
        'name' => 'Guard Ward',
        'department_id' => $department->id,
        'capacity' => 4,
        'ward_type' => 'general',
        'status' => 'active',
    ]);

    return Bed::create([
        'ward_id' => $ward->id,
        'bed_number' => 'G-01',
        'bed_type' => 'general',
        'daily_rate' => 1000,
        'status' => 'available',
    ]);
}

it('rejects admit on an OPD visit with 403 and creates no admission', function () {
    $visit = makeTypedVisit($this->patient, 'opd');
    $bed = makeAvailableBed();

    $this->post(route('visits.admit', $visit), [
        'bed_id' => $bed->id,
        'admission_notes' => 'should not admit',
    ])->assertForbidden();

    expect($visit->fresh()->admission)->toBeNull()
        ->and($visit->fresh()->status)->toBe('registered');
});

it('rejects admit on an Emergency visit with 403 and creates no admission', function () {
    $visit = makeTypedVisit($this->patient, 'emergency');
    $bed = makeAvailableBed();

    $this->post(route('visits.admit', $visit), [
        'bed_id' => $bed->id,
    ])->assertForbidden();

    expect($visit->fresh()->admission)->toBeNull();
});

it('admits an IPD visit successfully', function () {
    $visit = makeTypedVisit($this->patient, 'ipd');
    $bed = makeAvailableBed();

    $this->post(route('visits.admit', $visit), [
        'bed_id' => $bed->id,
        'admission_notes' => 'ok',
    ])->assertRedirect();

    expect($visit->fresh()->status)->toBe('admitted')
        ->and($visit->fresh()->admission)->not->toBeNull();
});

it('rejects triage on an OPD visit with 403 and creates no triage row', function () {
    $visit = makeTypedVisit($this->patient, 'opd');

    $this->post(route('visits.triage', $visit), [
        'priority_level' => 'urgent',
        'chief_complaint' => 'pain',
    ])->assertForbidden();

    expect($visit->fresh()->triage)->toBeNull();
});

it('rejects triage on an IPD visit with 403 and creates no triage row', function () {
    $visit = makeTypedVisit($this->patient, 'ipd');

    $this->post(route('visits.triage', $visit), [
        'priority_level' => 'urgent',
        'chief_complaint' => 'pain',
    ])->assertForbidden();

    expect($visit->fresh()->triage)->toBeNull();
});

it('triages an Emergency visit successfully', function () {
    $visit = makeTypedVisit($this->patient, 'emergency');

    $this->post(route('visits.triage', $visit), [
        'priority_level' => 'urgent',
        'chief_complaint' => 'chest pain',
        'pain_scale' => 5,
    ])->assertRedirect();

    expect($visit->fresh()->triage)->not->toBeNull()
        ->and($visit->fresh()->status)->toBe('triaged');
});
