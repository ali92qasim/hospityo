<?php

use App\Models\Department;
use App\Models\IpdVisit;
use App\Models\Patient;
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

it('renders ipd workflow with handler-driven ui flags', function () {
    config(['visits.dual_write_enabled' => false, 'visits.read_from_child.ipd' => true]);

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
        ->assertSee('Print IPD Report')
        ->assertSee('Select Bed for Admission')
        ->assertSee('Record New Vital Signs')
        ->assertSee('Vital Signs History');
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
