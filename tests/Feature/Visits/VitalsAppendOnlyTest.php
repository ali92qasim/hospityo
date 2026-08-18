<?php

use App\Models\Admission;
use App\Models\Bed;
use App\Models\IpdVisit;
use App\Models\OpdVisit;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\VitalSign;
use App\Models\Ward;
use App\Services\IpdClinicalService;
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

it('shows every ipd vital in clinical timeline and history after multiple saves', function () {
    Permission::findOrCreate('view visits', 'web');
    $this->user->givePermissionTo(['view visits']);

    $ward = Ward::create([
        'name' => 'Vitals Ward',
        'department_id' => \App\Models\Department::create(['name' => 'Medicine', 'code' => 'MED-VIT', 'status' => 'active'])->id,
        'capacity' => 5,
        'ward_type' => 'general',
        'status' => 'active',
    ]);

    $bed = Bed::create([
        'ward_id' => $ward->id,
        'bed_number' => 'VIT-01',
        'bed_type' => 'general',
        'daily_rate' => 2000,
        'status' => 'available',
    ]);

    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'ipd',
        'visit_datetime' => now(),
        'status' => 'admitted',
        'doctor_id' => null,
    ]);

    IpdVisit::create(['visit_id' => $visit->id]);

    Admission::create([
        'visit_id' => $visit->id,
        'bed_id' => $bed->id,
        'admission_date' => now(),
        'status' => 'active',
    ]);

    $bed->update(['status' => 'occupied']);

    foreach ([98.1, 98.2, 98.3, 98.4, 98.5] as $temperature) {
        $this->post(route('visits.vitals', $visit), [
            'temperature' => $temperature,
            'pulse_rate' => 72,
        ])->assertRedirect()->assertSessionHas('success');
    }

    expect(VitalSign::where('visit_id', $visit->id)->count())->toBe(5);

    $visit = $visit->fresh(['allVitalSigns.user', 'doctorVisitNotes.doctor', 'ipdGpeRecords.doctor']);

    expect(IpdClinicalService::clinicalTimelineEvents($visit)->where('type', 'vitals')->count())->toBe(5)
        ->and($visit->allVitalSigns)->toHaveCount(5);

    $response = $this->get(route('visits.workflow', $visit))->assertOk();

    foreach ([98.1, 98.2, 98.3, 98.4, 98.5] as $temperature) {
        $response->assertSee('Temp:</span> '.$temperature.'°F', false);
    }

    expect(substr_count($response->getContent(), 'data-vital-entry='))->toBe(5)
        ->and(substr_count($response->getContent(), 'Vital Signs History'))->toBe(0)
        ->and($response->getContent())->toContain('data-clinical-timeline-scroll');
});
