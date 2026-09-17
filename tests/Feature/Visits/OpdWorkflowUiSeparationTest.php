<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\OpdVisit;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Workflows\Handlers\OpdVisitHandler;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'OPD UI User',
        'email' => 'opd-ui@example.com',
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
        'name' => 'OPD UI Patient',
        'gender' => 'male',
        'age' => 38,
        'phone' => '03007770011',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03007770012',
        'emergency_relation' => 'Sibling',
    ]);
});

it('workflow blade has no opd visit_type conditionals', function () {
    $content = file_get_contents(resource_path('views/admin/visits/workflow.blade.php'));

    expect($content)->not->toMatch("/visit_type\\s*===?\\s*['\"]opd['\"]/");
});

it('renders opd workflow with handler-driven ui flags', function () {
    config(['visits.dual_write_enabled' => false, 'visits.read_from_child.opd' => true]);

    $department = Department::create(['name' => 'Medicine', 'code' => 'MED-OPD-UI', 'status' => 'active']);

    $doctor = Doctor::create([
        'name' => 'Dr. OPD UI',
        'doctor_no' => 'DOC-OPD-UI',
        'department_id' => $department->id,
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03001234567',
        'email' => 'dr-opd-ui@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1500,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
    ]);

    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'registered',
        'doctor_id' => $doctor->id,
    ]);

    OpdVisit::create(['visit_id' => $visit->id, 'queue_priority' => 'high']);

    $this->get(route('visits.workflow', $visit))
        ->assertOk()
        ->assertSee('data-workflow-layout="opd"', false)
        ->assertSee('data-landmark="opd-workflow-layout"', false)
        ->assertDontSee('data-landmark="ipd-episode-sidebar"', false)
        ->assertDontSee('data-landmark="emergency-triage-banner"', false)
        ->assertSee('High Queue Priority')
        ->assertSee('data-landmark="workflow-back-to-list"', false)
        ->assertSee('Back to OPD', false)
        ->assertSee('Record Vital Signs')
        ->assertSee('Doctor Assignment');

    $html = $this->get(route('visits.workflow', $visit))->getContent();
    $gpePos = strpos($html, 'id="gpe-content"');
    $nextVisitPos = strpos($html, 'id="next-visit-date"');
    $imagingPos = strpos($html, 'id="imaging-test-row-template"');

    expect($html)->toContain('id="lab-test-row-template"')
        ->and($html)->toContain('id="imaging-test-row-template"')
        ->and($gpePos)->not->toBeFalse()
        ->and($nextVisitPos)->not->toBeFalse()
        ->and($imagingPos)->not->toBeFalse()
        ->and($gpePos)->toBeLessThan($nextVisitPos)
        ->and($nextVisitPos)->toBeLessThan($imagingPos);
});

it('splits opd investigations into lab tests and imaging tabs with results below each form', function () {
    config(['visits.dual_write_enabled' => false, 'visits.read_from_child.opd' => true]);

    $department = Department::create(['name' => 'Medicine', 'code' => 'MED-OPD-LAB', 'status' => 'active']);

    $doctor = Doctor::create([
        'name' => 'Dr. OPD Lab',
        'doctor_no' => 'DOC-OPD-LAB',
        'department_id' => $department->id,
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03001234568',
        'email' => 'dr-opd-lab@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1500,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
    ]);

    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'registered',
        'doctor_id' => $doctor->id,
    ]);

    OpdVisit::create(['visit_id' => $visit->id, 'queue_priority' => 'high']);

    $html = $this->get(route('visits.workflow', $visit))->assertOk()->getContent();

    expect($html)
        ->toContain('data-workflow-section="lab"')
        ->toContain('data-workflow-section="imaging"')
        ->toContain('Lab Tests')
        ->toContain('id="lab-tests-form"')
        ->toContain('id="imaging-tests-form"')
        ->toContain('Ordered lab tests')
        ->toContain('Ordered imaging')
        ->not->toContain('Order Investigations')
        ->not->toContain('data-workflow-section="tests"')
        ->not->toContain('Ordered Investigations');

    $labContent = strpos($html, 'id="lab-content"');
    $labForm = strpos($html, 'id="lab-tests-form"');
    $labResults = strpos($html, 'Ordered lab tests');
    $imagingContent = strpos($html, 'id="imaging-content"');
    $imagingForm = strpos($html, 'id="imaging-tests-form"');
    $imagingResults = strpos($html, 'Ordered imaging');

    expect($labContent)->not->toBeFalse()
        ->and($labForm)->toBeGreaterThan($labContent)
        ->and($labResults)->toBeGreaterThan($labForm)
        ->and($imagingContent)->toBeGreaterThan($labResults)
        ->and($imagingForm)->toBeGreaterThan($imagingContent)
        ->and($imagingResults)->toBeGreaterThan($imagingForm);
});

it('opd handler exposes show_opd_ui and child queue priority', function () {
    config(['visits.dual_write_enabled' => false, 'visits.read_from_child.opd' => true]);

    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'registered',
    ]);

    OpdVisit::create(['visit_id' => $visit->id, 'queue_priority' => 'critical']);

    $handler = new OpdVisitHandler;
    $data = $handler->workflowData($visit->fresh(['opdDetails']));

    expect($data['show_opd_ui'])->toBeTrue()
        ->and($data['queue_priority'])->toBe('critical')
        ->and($handler->resolveInitialTab($visit))->toBe('vitals');
});
