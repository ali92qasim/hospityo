<?php

use App\Models\Admission;
use App\Models\Bed;
use App\Models\Department;
use App\Models\IpdVisit;
use App\Models\Patient;
use App\Models\Ward;
use App\Models\User;
use App\Models\Visit;
use App\Workflows\Handlers\IpdVisitHandler;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'IPD UI User',
        'email' => 'ipd-ui@example.com',
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
        'name' => 'IPD UI Patient',
        'gender' => 'male',
        'age' => 45,
        'phone' => '03008880001',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03008880002',
        'emergency_relation' => 'Sibling',
    ]);
});

it('workflow blade has no ipd visit_type conditionals', function () {
    $content = file_get_contents(resource_path('views/admin/visits/workflow.blade.php'));

    expect($content)->not->toMatch("/visit_type\\s*===?\\s*['\"]ipd['\"]/");
});

it('renders ipd workflow with handler-driven ui flags before admission', function () {
    config([
        'visits.dual_write_enabled' => false,
        'visits.read_from_child.ipd' => true,
        'visits.workflow_accordion_ui' => true,
    ]);

    Department::create(['name' => 'Medicine', 'code' => 'MED-UI', 'status' => 'active']);

    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'ipd',
        'visit_datetime' => now(),
        'status' => 'registered',
        'doctor_id' => null,
    ]);

    IpdVisit::create(['visit_id' => $visit->id]);

    $this->get(route('visits.workflow', $visit))
        ->assertOk()
        ->assertSee('data-workflow-layout="ipd"', false)
        ->assertSee('data-landmark="ipd-workflow-layout"', false)
        ->assertSee('data-landmark="ipd-episode-sidebar"', false)
        ->assertSee('data-landmark="ipd-clinical-feed"', false)
        ->assertDontSee('data-landmark="opd-workflow-layout"', false)
        ->assertSee('Print IPD Report')
        ->assertSee('Select Bed for Admission')
        ->assertDontSee('Record Vital Signs');
});

it('renders ipd workflow accordion after admission', function () {
    config([
        'visits.dual_write_enabled' => false,
        'visits.read_from_child.ipd' => true,
        'visits.workflow_accordion_ui' => true,
    ]);

    $department = Department::create(['name' => 'Medicine', 'code' => 'MED-ACC', 'status' => 'active']);

    $ward = Ward::create([
        'name' => 'Accordion Ward',
        'department_id' => $department->id,
        'capacity' => 5,
        'ward_type' => 'general',
        'status' => 'active',
    ]);

    $bed = Bed::create([
        'ward_id' => $ward->id,
        'bed_number' => 'ACC-01',
        'bed_type' => 'general',
        'daily_rate' => 2500,
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

    $this->get(route('visits.workflow', $visit))
        ->assertOk()
        ->assertSee('data-workflow-accordion-root', false)
        ->assertSee('Record Vital Signs')
        ->assertSee('Vital Signs History')
        ->assertSee('Clinical Timeline');
});

it('ipd handler exposes workflow permission flags', function () {
    config(['visits.dual_write_enabled' => false]);

    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'ipd',
        'visit_datetime' => now(),
        'status' => 'registered',
        'doctor_id' => null,
    ]);

    IpdVisit::create(['visit_id' => $visit->id]);

    $handler = new IpdVisitHandler;
    $data = $handler->workflowData($visit);

    expect($data['show_ipd_ui'])->toBeTrue()
        ->and($data['append_only_vitals'])->toBeTrue()
        ->and($handler->canConsult($visit))->toBeFalse()
        ->and($handler->resolveInitialTab($visit))->toBe('admission');
});
