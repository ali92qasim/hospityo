<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\EmergencyVisit;
use App\Models\Patient;
use App\Models\Triage;
use App\Models\User;
use App\Models\Visit;
use App\Workflows\Handlers\EmergencyVisitHandler;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Emergency UI User',
        'email' => 'emr-ui@example.com',
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
        'name' => 'Emergency UI Patient',
        'gender' => 'female',
        'age' => 42,
        'phone' => '03006660021',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03006660022',
        'emergency_relation' => 'Parent',
    ]);
});

it('workflow blade has no emergency visit_type conditionals', function () {
    $content = file_get_contents(resource_path('views/admin/visits/workflow.blade.php'));

    expect($content)->not->toMatch("/visit_type\\s*===?\\s*['\"]emergency['\"]/");
});

it('renders emergency workflow with handler-driven ui flags', function () {
    config(['visits.dual_write_enabled' => false, 'visits.read_from_child.emergency' => true]);

    $department = Department::create(['name' => 'Emergency', 'code' => 'EMR-UI', 'status' => 'active']);

    $doctor = Doctor::create([
        'name' => 'Dr. EMR UI',
        'doctor_no' => 'DOC-EMR-UI',
        'department_id' => $department->id,
        'specialization' => 'Emergency Medicine',
        'qualification' => 'MBBS',
        'phone' => '03006660023',
        'email' => 'dr-emr-ui@example.com',
        'gender' => 'male',
        'experience_years' => 10,
        'consultation_fee' => 2500,
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
        'priority_level' => 'critical',
        'chief_complaint' => 'Severe trauma',
        'pain_scale' => 9,
        'triaged_by' => $this->user->id,
        'triaged_at' => now(),
    ]);

    $this->get(route('visits.workflow', $visit))
        ->assertOk()
        ->assertSee('Triage Completed')
        ->assertSee('Severe trauma')
        ->assertSee('Doctor Assignment')
        ->assertSee('Emergency Care')
        ->assertDontSee('Order Investigations');
});

it('emergency handler exposes show_emergency_ui and triage flags', function () {
    config(['visits.dual_write_enabled' => false, 'visits.read_from_child.emergency' => true]);

    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'emergency',
        'visit_datetime' => now(),
        'status' => 'registered',
    ]);

    EmergencyVisit::create(['visit_id' => $visit->id]);

    $handler = new EmergencyVisitHandler;
    $data = $handler->workflowData($visit->fresh(['emergencyDetails', 'triage']));

    expect($data['show_emergency_ui'])->toBeTrue()
        ->and($data['show_investigations'])->toBeFalse()
        ->and($data['triage_completed'])->toBeFalse()
        ->and($handler->resolveInitialTab($visit))->toBe('triage');
});
