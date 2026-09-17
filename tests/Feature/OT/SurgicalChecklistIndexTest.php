<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Surgery;
use App\Models\User;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);

    otCatalogTenant(['ot', 'ot.checklist']);
});

function makeScheduledSurgery(User $creator): Surgery
{
    $patient = Patient::create([
        'name' => 'Checklist Patient',
        'gender' => 'male',
        'age' => 40,
        'phone' => '03009998888',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03007776666',
        'emergency_relation' => 'Brother',
    ]);

    $department = Department::create([
        'name' => 'Medicine',
        'code' => 'MED-'.uniqid(),
        'status' => 'active',
    ]);

    $doctor = Doctor::create([
        'name' => 'Dr. Checklist',
        'doctor_no' => 'DOC-CL-'.uniqid(),
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005554444',
        'email' => 'dr-cl-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    return Surgery::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'procedure_name' => 'Appendectomy',
        'scheduled_date' => today(),
        'status' => 'scheduled',
        'created_by' => $creator->id,
    ]);
}

it('lets a nurse without view surgeries open the checklist index and a pending surgery', function () {
    $nurse = otCatalogUser(['manage surgical checklists']);
    $creator = otCatalogUser(['view surgeries']);
    $surgery = makeScheduledSurgery($creator);

    $this->actingAs($nurse)
        ->get(route('ot.checklist.index'))
        ->assertOk()
        ->assertSee($surgery->surgery_number)
        ->assertSee(route('ot.checklist.show', $surgery), false);

    $this->actingAs($nurse)
        ->get(route('ot.checklist.show', $surgery))
        ->assertOk();

    $this->actingAs($nurse)
        ->get(route('ot.theatres'))
        ->assertForbidden();
});

it('hides completed surgeries and cancelled surgeries from the checklist index', function () {
    $nurse = otCatalogUser(['manage surgical checklists']);
    $creator = otCatalogUser(['view surgeries']);
    $pending = makeScheduledSurgery($creator);
    $done = makeScheduledSurgery($creator);
    $done->update(['status' => 'completed']);
    $cancelled = makeScheduledSurgery($creator);
    $cancelled->update(['status' => 'cancelled']);

    $this->actingAs($nurse)
        ->get(route('ot.checklist.index'))
        ->assertOk()
        ->assertSee($pending->surgery_number)
        ->assertDontSee($done->surgery_number)
        ->assertDontSee($cancelled->surgery_number);
});

it('shows checklist in the ot sidebar for manage surgical checklists without view surgeries', function () {
    $group = collect(app(\App\Services\SidebarService::class)->build(
        otCatalogUser(['manage surgical checklists']),
        otCatalogTenant(['ot', 'ot.checklist'])
    ))->firstWhere('id', 'ot');

    expect($group)->not->toBeNull()
        ->and(collect($group['items'])->pluck('label')->all())->toBe(['Checklist'])
        ->and(collect($group['items'])->first()['route'])->toBe('ot.checklist.index');
});
