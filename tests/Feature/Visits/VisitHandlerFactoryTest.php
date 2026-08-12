<?php

use App\Models\Department;
use App\Models\IpdVisit;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Workflows\Handlers\IpdVisitHandler;
use App\Workflows\Handlers\OpdVisitHandler;
use App\Workflows\VisitHandlerFactory;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Handler User',
        'email' => 'handler@example.com',
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
        'name' => 'Handler Patient',
        'gender' => 'male',
        'age' => 40,
        'phone' => '03007770001',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03007770002',
        'emergency_relation' => 'Sibling',
    ]);
});

it('resolves handler by visit type', function () {
    config(['visits.dual_write_enabled' => false]);

    $opd = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'registered',
    ]);

    $ipd = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'ipd',
        'visit_datetime' => now(),
        'status' => 'registered',
    ]);

    expect(VisitHandlerFactory::for($opd))->toBeInstanceOf(OpdVisitHandler::class)
        ->and(VisitHandlerFactory::for($ipd))->toBeInstanceOf(IpdVisitHandler::class);
});

it('renders ipd workflow using handler data', function () {
    config(['visits.dual_write_enabled' => false, 'visits.read_from_child.ipd' => true]);

    $department = Department::create(['name' => 'Medicine', 'code' => 'MED-H', 'status' => 'active']);

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
        ->assertSee('data-landmark="ipd-episode-sidebar"', false)
        ->assertSee('Awaiting admission');
});
