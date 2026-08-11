<?php

use App\Models\OpdVisit;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\VitalSign;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    config(['visits.dual_write_enabled' => false]);

    $this->user = User::create([
        'name' => 'Vitals Append User',
        'email' => 'vitals-append@example.com',
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
        'name' => 'Append Vitals Patient',
        'gender' => 'female',
        'age' => 35,
        'phone' => '03008880001',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03008880002',
        'emergency_relation' => 'Parent',
    ]);
});

function createOpdVisitForVitals(Patient $patient): Visit
{
    $visit = Visit::create([
        'patient_id' => $patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'registered',
        'priority' => 'medium',
    ]);

    OpdVisit::create(['visit_id' => $visit->id, 'queue_priority' => 'medium']);

    return $visit;
}

it('creates a new opd vitals row on each save', function () {
    $visit = createOpdVisitForVitals($this->patient);

    $this->post(route('visits.vitals', $visit), [
        'temperature' => 98.6,
        'pulse_rate' => 72,
    ])->assertRedirect()->assertSessionHas('success');

    $this->post(route('visits.vitals', $visit), [
        'temperature' => 99.1,
        'pulse_rate' => 80,
    ])->assertRedirect()->assertSessionHas('success');

    expect(VitalSign::where('visit_id', $visit->id)->count())->toBe(2);
});

it('returns latest vitals through vitalSigns relation for opd', function () {
    $visit = createOpdVisitForVitals($this->patient);

    VitalSign::create([
        'visit_id' => $visit->id,
        'temperature' => 98.0,
        'recorded_by' => $this->user->id,
    ]);

    VitalSign::create([
        'visit_id' => $visit->id,
        'temperature' => 100.2,
        'recorded_by' => $this->user->id,
    ]);

    expect((float) $visit->fresh()->vitalSigns?->temperature)->toBe(100.2);
});

it('creates a new emergency vitals row on each save', function () {
    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'emergency',
        'visit_datetime' => now(),
        'status' => 'registered',
        'priority' => 'high',
    ]);

    \App\Models\EmergencyVisit::create(['visit_id' => $visit->id]);

    $this->post(route('visits.vitals', $visit), [
        'blood_pressure' => '120/80',
    ])->assertRedirect()->assertSessionHas('success');

    $this->post(route('visits.vitals', $visit), [
        'blood_pressure' => '130/85',
    ])->assertRedirect()->assertSessionHas('success');

    expect(VitalSign::where('visit_id', $visit->id)->count())->toBe(2)
        ->and($visit->fresh()->vitalSigns?->blood_pressure)->toBe('130/85');
});
