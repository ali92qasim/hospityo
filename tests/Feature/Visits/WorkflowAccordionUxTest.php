<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\EmergencyVisit;
use App\Models\OpdVisit;
use App\Models\Patient;
use App\Models\Triage;
use App\Models\User;
use App\Models\Visit;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    config([
        'visits.workflow_accordion_ui' => true,
        'visits.dual_write_enabled' => false,
    ]);

    Permission::findOrCreate('view visits', 'web');

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    $this->patient = Patient::create([
        'name' => 'Accordion Patient',
        'gender' => 'male',
        'age' => 40,
        'phone' => '03008887766',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03008887767',
        'emergency_relation' => 'Sibling',
    ]);
});

function makeAccordionUser(): User
{
    $user = User::create([
        'name' => 'Accordion User',
        'email' => 'accordion-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo(['view visits']);

    return $user;
}

it('renders workflow accordion section with aria expanded and state chip', function () {
    $html = view('components.workflow-accordion-section', [
        'id' => 'vitals',
        'title' => 'Record Vital Signs',
        'icon' => 'fa-heartbeat',
        'iconColor' => 'text-red-500',
        'state' => 'next',
        'open' => true,
        'disabled' => false,
    ])->with('slot', '<p>Vitals form</p>')->render();

    expect($html)
        ->toContain('data-workflow-section="vitals"')
        ->toContain('aria-expanded="true"')
        ->toContain('Record Vital Signs')
        ->toContain('Vitals form');
});

it('places assign doctor before vitals section on opd workflow', function () {
    $user = makeAccordionUser();
    $this->actingAs($user);

    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'registered',
    ]);

    OpdVisit::create(['visit_id' => $visit->id, 'queue_priority' => 'medium']);

    $response = $this->get(route('visits.workflow', $visit));

    $response->assertOk()
        ->assertSee('data-workflow-accordion-root', false)
        ->assertSee('data-landmark="workflow-doctor-assignment"', false);

    $content = $response->getContent();
    $doctorPos = strpos($content, 'Doctor Assignment');
    $vitalsPos = strpos($content, 'Record Vital Signs');

    expect($doctorPos)->not->toBeFalse()
        ->and($vitalsPos)->not->toBeFalse()
        ->and($doctorPos)->toBeLessThan($vitalsPos);
});

it('places triage before doctor before vitals on emergency workflow', function () {
    $user = makeAccordionUser();
    $this->actingAs($user);

    $department = Department::create(['name' => 'Emergency', 'code' => 'EMR-ACC', 'status' => 'active']);

    $doctor = Doctor::create([
        'name' => 'Dr. Accordion EMR',
        'doctor_no' => 'DOC-EMR-ACC',
        'department_id' => $department->id,
        'specialization' => 'Emergency',
        'qualification' => 'MBBS',
        'phone' => '03008887768',
        'email' => 'dr-emr-acc@example.com',
        'gender' => 'male',
        'experience_years' => 8,
        'consultation_fee' => 2000,
        'shift_start' => '08:00:00',
        'shift_end' => '20:00:00',
        'status' => 'active',
    ]);

    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'emergency',
        'visit_datetime' => now(),
        'status' => 'triaged',
        'doctor_id' => $doctor->id,
    ]);

    EmergencyVisit::create(['visit_id' => $visit->id]);

    Triage::create([
        'visit_id' => $visit->id,
        'priority_level' => 'urgent',
        'chief_complaint' => 'Chest pain',
        'pain_scale' => 7,
        'triaged_by' => $user->id,
        'triaged_at' => now(),
    ]);

    $response = $this->get(route('visits.workflow', $visit));
    $content = $response->getContent();

    $triagePos = strpos($content, 'Triage Completed');
    $doctorPos = strpos($content, 'Doctor Assignment');
    $vitalsPos = strpos($content, 'Record Vital Signs');

    expect($triagePos)->not->toBeFalse()
        ->and($doctorPos)->not->toBeFalse()
        ->and($vitalsPos)->not->toBeFalse()
        ->and($triagePos)->toBeLessThan($doctorPos)
        ->and($doctorPos)->toBeLessThan($vitalsPos);
});

it('locks consultation accordion when no doctor on opd workflow', function () {
    $user = makeAccordionUser();
    $this->actingAs($user);

    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'registered',
    ]);

    OpdVisit::create(['visit_id' => $visit->id, 'queue_priority' => 'medium']);

    $this->get(route('visits.workflow', $visit))
        ->assertOk()
        ->assertSee('data-workflow-section="consultation"', false)
        ->assertSee('Assign doctor first', false);
});
