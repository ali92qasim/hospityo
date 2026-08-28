<?php

use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    config(['visits.require_typed_visit_routes' => true]);

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    Permission::findOrCreate('view visits', 'web');
    Permission::findOrCreate('create visits', 'web');
    Permission::findOrCreate('edit visits', 'web');

    $this->patient = Patient::create([
        'name' => 'Simplify Patient',
        'gender' => 'male',
        'age' => 35,
        'phone' => '03009998877',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03009998878',
        'emergency_relation' => 'Sibling',
    ]);
});

function makeVisitUser(array $permissions): User
{
    $user = User::create([
        'name' => 'Visit Flow User',
        'email' => 'visit-flow-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

it('redirects generic visits index to opd typed list', function () {
    $user = makeVisitUser(['view visits']);

    $this->actingAs($user)
        ->get(route('visits.index'))
        ->assertRedirect(route('visits.index', ['visit_type' => 'opd']));
});

it('redirects generic visits create to opd typed create', function () {
    $user = makeVisitUser(['view visits', 'create visits']);

    $this->actingAs($user)
        ->get(route('visits.create'))
        ->assertRedirect(route('visits.create', ['visit_type' => 'opd']));
});

it('opd create page does not show visit type selector', function () {
    $user = makeVisitUser(['view visits', 'create visits']);

    $this->actingAs($user)
        ->get(route('visits.create', ['visit_type' => 'opd']))
        ->assertOk()
        ->assertDontSee('Select Visit Type')
        ->assertDontSee('<select name="visit_type"', false)
        ->assertSee('New OPD Patient')
        ->assertSee('Save & Add Another', false);
});

it('shows new opd patient button on list for users with create visits', function () {
    $user = makeVisitUser(['view visits', 'create visits']);

    $this->actingAs($user)
        ->get(route('visits.index', ['visit_type' => 'opd']))
        ->assertOk()
        ->assertSee('+ New OPD Patient')
        ->assertDontSee('data-default-date-filter=', false);
});

it('hides new opd patient button for nurses', function () {
    $user = makeVisitUser(['view visits']);

    $this->actingAs($user)
        ->get(route('visits.index', ['visit_type' => 'opd']))
        ->assertOk()
        ->assertDontSee('New OPD Patient');
});

it('stores visit with hidden visit type and supports save and add another', function () {
    $user = makeVisitUser(['view visits', 'create visits']);

    $this->actingAs($user)
        ->post(route('visits.store'), [
            'patient_id' => $this->patient->id,
            'visit_type' => 'opd',
            'visit_datetime' => now()->format('Y-m-d H:i'),
            'save_and_add_another' => '1',
        ])
        ->assertRedirect(route('visits.create', ['visit_type' => 'opd']))
        ->assertSessionHas('success');

    expect(Visit::where('patient_id', $this->patient->id)->where('visit_type', 'opd')->exists())->toBeTrue();
});

it('quick registers opd visit from patient list and opens workflow without flash', function () {
    $user = makeVisitUser(['view visits', 'create visits', 'edit visits']);

    $this->actingAs($user)
        ->post(route('visits.quick-register'), [
            'patient_id' => $this->patient->id,
            'visit_type' => 'opd',
            'from' => 'patients',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('success');

    $visit = Visit::where('patient_id', $this->patient->id)->where('visit_type', 'opd')->latest('id')->first();

    expect($visit)->not->toBeNull();

    $this->get(route('visits.workflow', $visit))
        ->assertOk()
        ->assertSee('name="spo2"', false)
        ->assertSee('name="weight"', false)
        ->assertSee('name="height"', false);
});

it('quick registers emergency visit from patient list and opens workflow', function () {
    $user = makeVisitUser(['view visits', 'create visits', 'edit visits']);

    $response = $this->actingAs($user)
        ->post(route('visits.quick-register'), [
            'patient_id' => $this->patient->id,
            'visit_type' => 'emergency',
            'from' => 'patients',
        ]);

    $visit = Visit::where('patient_id', $this->patient->id)->where('visit_type', 'emergency')->latest('id')->first();

    $response->assertRedirect(route('visits.workflow', ['visit' => $visit, 'from' => 'patients']))
        ->assertSessionMissing('success');
});

it('quick registers opd visit from patient list and back link goes to patients', function () {
    $user = makeVisitUser(['view visits', 'create visits', 'edit visits']);

    $this->actingAs($user)
        ->post(route('visits.quick-register'), [
            'patient_id' => $this->patient->id,
            'visit_type' => 'opd',
            'from' => 'patients',
        ]);

    $visit = Visit::where('patient_id', $this->patient->id)->where('visit_type', 'opd')->latest('id')->first();

    expect($visit)->not->toBeNull();

    $this->get(route('visits.workflow', ['visit' => $visit, 'from' => 'patients']))
        ->assertOk()
        ->assertSee('data-landmark="workflow-back-to-list"', false)
        ->assertSee('Back to Patients', false)
        ->assertSee('href="'.e(route('patients.index')).'"', false)
        ->assertDontSee('Back to OPD', false);
});

it('quick registers emergency visit from patient list and back link goes to patients', function () {
    $user = makeVisitUser(['view visits', 'create visits', 'edit visits']);

    $response = $this->actingAs($user)
        ->post(route('visits.quick-register'), [
            'patient_id' => $this->patient->id,
            'visit_type' => 'emergency',
            'from' => 'patients',
        ]);

    $visit = Visit::where('patient_id', $this->patient->id)->where('visit_type', 'emergency')->latest('id')->first();

    $response->assertRedirect(route('visits.workflow', ['visit' => $visit, 'from' => 'patients']));

    $this->get(route('visits.workflow', ['visit' => $visit, 'from' => 'patients']))
        ->assertOk()
        ->assertSee('Back to Patients', false)
        ->assertSee('href="'.e(route('patients.index')).'"', false)
        ->assertDontSee('Back to Emergency', false);
});

it('emergency workflow back link goes to emergency listing not opd', function () {
    $user = makeVisitUser(['view visits', 'create visits', 'edit visits']);

    $this->actingAs($user)
        ->post(route('visits.quick-register'), [
            'patient_id' => $this->patient->id,
            'visit_type' => 'emergency',
        ]);

    $visit = Visit::where('patient_id', $this->patient->id)->where('visit_type', 'emergency')->latest('id')->first();

    $this->get(route('visits.workflow', $visit))
        ->assertOk()
        ->assertSee('data-landmark="workflow-back-to-list"', false)
        ->assertSee('Back to Emergency', false)
        ->assertSee('href="'.e(route('visits.index', ['visit_type' => 'emergency'])).'"', false)
        ->assertDontSee('Back to OPD', false)
        ->assertDontSee('Back to Visits', false);
});

it('patients listing quick register forms include patients origin', function () {
    $script = file_get_contents(resource_path('js/patients-index.js'));

    expect($script)
        ->toContain('name="from"')
        ->toContain('value="patients"');
});

it('edit visit page shows read only visit type badge not select', function () {
    $user = makeVisitUser(['view visits', 'edit visits']);

    $visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'registered',
    ]);

    $this->actingAs($user)
        ->get(route('visits.edit', $visit))
        ->assertOk()
        ->assertSee('OPD')
        ->assertDontSee('<select name="visit_type"', false);
});
