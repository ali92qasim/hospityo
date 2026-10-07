<?php

use App\Models\AnaesthesiaRecord;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\ModuleRegistry;
use App\Models\OperationTheatre;
use App\Models\OperativeVital;
use App\Models\Patient;
use App\Models\PostOpMonitoring;
use App\Models\PreAnaesthesiaCheckup;
use App\Models\Surgery;
use App\Models\SurgicalChecklist;
use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Permission;

/*
|--------------------------------------------------------------------------
| OT surgery route scoping (RBAC wave 1, Task 5)
|--------------------------------------------------------------------------
| Each of the 27 routes in the OT surgeries group accepts exactly one action
| permission. Exceptions by design: `check-conflicts` accepts create|edit
| surgeries (read-only helper for both forms), theatre writes need
| `manage theatres` (OT-1), and `cancel` needs `delete surgeries` (OT-2).
*/

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);

    otScopeTenant();

    $this->owner = User::create([
        'name' => 'OT Fixture Owner',
        'email' => 'ot-scope-owner-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->patient = Patient::create([
        'name' => 'OT Scope Patient',
        'gender' => 'male',
        'age' => 45,
        'phone' => '03001112222',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03003334444',
        'emergency_relation' => 'Brother',
    ]);

    $department = Department::create([
        'name' => 'Surgery',
        'code' => 'SUR-'.uniqid(),
        'status' => 'active',
    ]);

    $this->doctor = Doctor::create([
        'name' => 'Dr. OT Scope',
        'doctor_no' => 'DOC-OTS-'.uniqid(),
        'specialization' => 'General Surgery',
        'qualification' => 'MBBS, FCPS',
        'phone' => '03005556666',
        'email' => 'dr-ots-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 10,
        'consultation_fee' => 2000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $this->theatre = OperationTheatre::create([
        'name' => 'OT Scope Theatre',
        'type' => 'general',
        'status' => 'available',
        'is_active' => true,
    ]);

    // Scheduled, PAC cleared, checklist sign-in complete: `start` reaches its write.
    $this->scheduled = otScopeSurgery($this, 'scheduled', 'Scoped Appendectomy');

    PreAnaesthesiaCheckup::create([
        'surgery_id' => $this->scheduled->id,
        'patient_id' => $this->patient->id,
        'requested_by' => $this->owner->id,
        'status' => 'cleared',
        'cleared_at' => now(),
    ]);

    $checklist = SurgicalChecklist::create([
        'surgery_id' => $this->scheduled->id,
        'status' => 'sign_in_done',
        'sign_in_completed_at' => now(),
    ]);
    $checklist->items()->create([
        'phase' => 'sign_in',
        'item_key' => 'patient_identity_confirmed',
        'label' => 'Patient identity confirmed',
        'is_checked' => true,
        'sort_order' => 1,
    ]);

    $this->live = otScopeSurgery($this, 'in_progress', 'Scoped Cholecystectomy');
});

function otScopeTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    // The 27 routes resolve to the `ot` module (CheckModule); the children are
    // enabled too so no plan-level 403 can masquerade as a permission 403.
    $modules = ['ot', ...ModuleRegistry::OT_CHILD_SLUGS];
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, $modules, true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function otScopeUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'OT Scope User',
        'email' => 'ot-scope-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

function otScopeSurgery($test, string $status, string $procedure): Surgery
{
    return Surgery::create([
        'patient_id' => $test->patient->id,
        'doctor_id' => $test->doctor->id,
        'operation_theatre_id' => $test->theatre->id,
        'surgery_type' => 'elective',
        'procedure_name' => $procedure,
        'scheduled_date' => today(),
        'status' => $status,
        'created_by' => $test->owner->id,
    ]);
}

/** Resolve the dataset's fixture names to route parameters. */
function otScopeParams($test, array $fixtures): array
{
    $map = [
        'theatre' => fn () => ['theatre' => $test->theatre->id],
        'scheduled' => fn () => ['surgery' => $test->scheduled->id],
        'live' => fn () => ['surgery' => $test->live->id],
        'conflicts' => fn () => [
            'operation_theatre_id' => $test->theatre->id,
            'scheduled_date' => today()->toDateString(),
            'scheduled_start_time' => '10:00',
        ],
    ];

    $params = [];
    foreach ($fixtures as $fixture) {
        $params += $map[$fixture]();
    }

    return $params;
}

/** A valid payload for each write route, so a request that passes the gate really writes. */
function otScopePayload($test, string $route): array
{
    return match ($route) {
        'ot.theatres.store' => ['name' => 'OT Scope New Theatre', 'type' => 'cardiac'],
        'ot.theatres.update' => ['name' => 'OT Scope Renamed', 'type' => 'general', 'status' => 'maintenance', 'is_active' => true],
        'ot.surgeries.store' => [
            'patient_id' => $test->patient->id,
            'doctor_id' => $test->doctor->id,
            'surgery_type' => 'elective',
            'procedure_name' => 'Scoped Hernia Repair',
            'scheduled_date' => today()->addDay()->toDateString(),
        ],
        'ot.surgeries.update' => [
            'patient_id' => $test->patient->id,
            'doctor_id' => $test->doctor->id,
            'operation_theatre_id' => $test->theatre->id,
            'surgery_type' => 'elective',
            'procedure_name' => 'Scoped Appendectomy Revised',
            'scheduled_date' => today()->toDateString(),
        ],
        'ot.surgeries.complete' => ['post_op_diagnosis' => 'Scoped post-op diagnosis'],
        'ot.surgeries.postpone' => ['postponed_reason' => 'Scoped postpone reason'],
        'ot.surgeries.cancel' => ['cancelled_reason' => 'Scoped cancel reason'],
        'ot.monitoring.store-anaesthesia' => ['anaesthetist_id' => $test->owner->id, 'anaesthesia_type' => 'general'],
        'ot.monitoring.store-vitals' => ['recorded_at' => now()->toDateTimeString(), 'heart_rate' => '80', 'spo2' => '98'],
        'ot.monitoring.store-post-op' => ['recorded_at' => now()->toDateTimeString(), 'phase' => 'pacu', 'heart_rate' => '76'],
        default => [],
    };
}

function otScopeRequest($test, string $method, string $route, array $fixtures)
{
    $url = route($route, otScopeParams($test, $fixtures));

    return $method === 'get'
        ? $test->get($url)
        : $test->{$method}($url, otScopePayload($test, $route));
}

// ── (a) Deny: a wrong-verb holder that passes today must get 403 ──────────

dataset('ot escalations', [
    // Every form/write route with view surgeries only.
    'theatres.create via view' => ['get', 'ot.theatres.create', [], ['view surgeries']],
    'theatres.store via view' => ['post', 'ot.theatres.store', [], ['view surgeries']],
    'theatres.edit via view' => ['get', 'ot.theatres.edit', ['theatre'], ['view surgeries']],
    'theatres.update via view' => ['put', 'ot.theatres.update', ['theatre'], ['view surgeries']],
    'surgeries.create via view' => ['get', 'ot.surgeries.create', [], ['view surgeries']],
    'surgeries.store via view' => ['post', 'ot.surgeries.store', [], ['view surgeries']],
    'surgeries.edit via view' => ['get', 'ot.surgeries.edit', ['scheduled'], ['view surgeries']],
    'surgeries.update via view' => ['put', 'ot.surgeries.update', ['scheduled'], ['view surgeries']],
    'surgeries.start via view' => ['post', 'ot.surgeries.start', ['scheduled'], ['view surgeries']],
    'surgeries.complete via view' => ['post', 'ot.surgeries.complete', ['live'], ['view surgeries']],
    'surgeries.postpone via view' => ['post', 'ot.surgeries.postpone', ['scheduled'], ['view surgeries']],
    'surgeries.cancel via view' => ['post', 'ot.surgeries.cancel', ['scheduled'], ['view surgeries']],
    'monitoring.anaesthesia via view' => ['get', 'ot.monitoring.anaesthesia', ['live'], ['view surgeries']],
    'monitoring.store-anaesthesia via view' => ['post', 'ot.monitoring.store-anaesthesia', ['live'], ['view surgeries']],
    'monitoring.vitals via view' => ['get', 'ot.monitoring.vitals', ['live'], ['view surgeries']],
    'monitoring.store-vitals via view' => ['post', 'ot.monitoring.store-vitals', ['live'], ['view surgeries']],
    'monitoring.post-op via view' => ['get', 'ot.monitoring.post-op', ['live'], ['view surgeries']],
    'monitoring.store-post-op via view' => ['post', 'ot.monitoring.store-post-op', ['live'], ['view surgeries']],
    // Every read route with create surgeries only.
    'calendar via create' => ['get', 'ot.calendar', [], ['create surgeries']],
    'calendar.events via create' => ['get', 'ot.calendar.events', [], ['create surgeries']],
    'theatres via create' => ['get', 'ot.theatres', [], ['create surgeries']],
    'surgeries.index via create' => ['get', 'ot.surgeries.index', [], ['create surgeries']],
    'surgeries.show via create' => ['get', 'ot.surgeries.show', ['scheduled'], ['create surgeries']],
    'monitoring.vitals-data via create' => ['get', 'ot.monitoring.vitals-data', ['live'], ['create surgeries']],
    // The conflict helper is for the create/edit forms only.
    'check-conflicts via view' => ['get', 'ot.check-conflicts', ['conflicts'], ['view surgeries']],
    // OT-1: theatre configuration needs manage theatres.
    'theatres.create via edit' => ['get', 'ot.theatres.create', [], ['edit surgeries']],
    'theatres.store via edit' => ['post', 'ot.theatres.store', [], ['edit surgeries']],
    'theatres.edit via edit' => ['get', 'ot.theatres.edit', ['theatre'], ['edit surgeries']],
    'theatres.update via edit' => ['put', 'ot.theatres.update', ['theatre'], ['edit surgeries']],
    // OT-2: cancel needs delete surgeries.
    'surgeries.cancel via edit' => ['post', 'ot.surgeries.cancel', ['scheduled'], ['edit surgeries']],
]);

it('forbids a wrong-verb permission on each OT surgery route', function (string $method, string $route, array $fixtures, array $permissions) {
    $this->actingAs(otScopeUser($permissions));

    otScopeRequest($this, $method, $route, $fixtures)->assertForbidden();
})->with('ot escalations');

// ── (b) Positive control: exactly the new permission passes ───────────────

dataset('ot positive controls', [
    'calendar' => ['get', 'ot.calendar', [], 'view surgeries'],
    'calendar.events' => ['get', 'ot.calendar.events', [], 'view surgeries'],
    'check-conflicts via create' => ['get', 'ot.check-conflicts', ['conflicts'], 'create surgeries'],
    'check-conflicts via edit' => ['get', 'ot.check-conflicts', ['conflicts'], 'edit surgeries'],
    'theatres' => ['get', 'ot.theatres', [], 'view surgeries'],
    'theatres.create' => ['get', 'ot.theatres.create', [], 'manage theatres'],
    'theatres.store' => ['post', 'ot.theatres.store', [], 'manage theatres'],
    'theatres.edit' => ['get', 'ot.theatres.edit', ['theatre'], 'manage theatres'],
    'theatres.update' => ['put', 'ot.theatres.update', ['theatre'], 'manage theatres'],
    'surgeries.index' => ['get', 'ot.surgeries.index', [], 'view surgeries'],
    'surgeries.create' => ['get', 'ot.surgeries.create', [], 'create surgeries'],
    'surgeries.store' => ['post', 'ot.surgeries.store', [], 'create surgeries'],
    'surgeries.show' => ['get', 'ot.surgeries.show', ['scheduled'], 'view surgeries'],
    'surgeries.edit' => ['get', 'ot.surgeries.edit', ['scheduled'], 'edit surgeries'],
    'surgeries.update' => ['put', 'ot.surgeries.update', ['scheduled'], 'edit surgeries'],
    'surgeries.start' => ['post', 'ot.surgeries.start', ['scheduled'], 'edit surgeries'],
    'surgeries.complete' => ['post', 'ot.surgeries.complete', ['live'], 'edit surgeries'],
    'surgeries.cancel' => ['post', 'ot.surgeries.cancel', ['scheduled'], 'delete surgeries'],
    'surgeries.postpone' => ['post', 'ot.surgeries.postpone', ['scheduled'], 'edit surgeries'],
    'monitoring.anaesthesia' => ['get', 'ot.monitoring.anaesthesia', ['live'], 'edit surgeries'],
    'monitoring.store-anaesthesia' => ['post', 'ot.monitoring.store-anaesthesia', ['live'], 'edit surgeries'],
    'monitoring.vitals' => ['get', 'ot.monitoring.vitals', ['live'], 'edit surgeries'],
    'monitoring.store-vitals' => ['post', 'ot.monitoring.store-vitals', ['live'], 'edit surgeries'],
    'monitoring.vitals-data' => ['get', 'ot.monitoring.vitals-data', ['live'], 'view surgeries'],
    'monitoring.post-op' => ['get', 'ot.monitoring.post-op', ['live'], 'edit surgeries'],
    'monitoring.store-post-op' => ['post', 'ot.monitoring.store-post-op', ['live'], 'edit surgeries'],
]);

it('allows exactly the new permission on each OT surgery route', function (string $method, string $route, array $fixtures, string $permission) {
    $this->actingAs(otScopeUser([$permission]));

    $response = otScopeRequest($this, $method, $route, $fixtures);

    if ($method === 'get') {
        $response->assertOk();

        return;
    }

    // The write ran: a redirect with the success flash, no validation or gate error.
    $response->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('error')
        ->assertSessionHas('success');
})->with('ot positive controls');

// ── Superset sanity: all 4 surgery permissions are not manage theatres ────

it('forbids theatre configuration to a holder of all four surgery permissions without manage theatres', function (string $method, string $route, array $fixtures) {
    $this->actingAs(otScopeUser(['view surgeries', 'create surgeries', 'edit surgeries', 'delete surgeries']));

    otScopeRequest($this, $method, $route, $fixtures)->assertForbidden();
})->with([
    'theatres.create' => ['get', 'ot.theatres.create', []],
    'theatres.store' => ['post', 'ot.theatres.store', []],
    'theatres.edit' => ['get', 'ot.theatres.edit', ['theatre']],
    'theatres.update' => ['put', 'ot.theatres.update', ['theatre']],
]);

// ── (c) Side effects: a blocked write writes nothing ──────────────────────
// The "nothing written" assertions run before the status assertion, so that
// before the fix these tests fail because the write actually happened.

it('leaves the surgery status unchanged when view surgeries posts a status transition', function (string $route, string $fixture, string $status) {
    $this->actingAs(otScopeUser(['view surgeries']));

    $response = otScopeRequest($this, 'post', $route, [$fixture]);

    expect($this->{$fixture}->fresh()->status)->toBe($status);
    $response->assertForbidden();
})->with([
    'start' => ['ot.surgeries.start', 'scheduled', 'scheduled'],
    'complete' => ['ot.surgeries.complete', 'live', 'in_progress'],
    'postpone' => ['ot.surgeries.postpone', 'scheduled', 'scheduled'],
    'cancel' => ['ot.surgeries.cancel', 'scheduled', 'scheduled'],
]);

it('does not cancel the surgery when edit surgeries posts cancel with a reason', function () {
    $this->actingAs(otScopeUser(['edit surgeries']));

    $response = otScopeRequest($this, 'post', 'ot.surgeries.cancel', ['scheduled']);

    $surgery = $this->scheduled->fresh();
    expect($surgery->status)->not->toBe('cancelled')
        ->and($surgery->cancelled_reason)->toBeNull();
    $response->assertForbidden();
});

it('cancels the surgery for a delete surgeries holder', function () {
    $this->actingAs(otScopeUser(['delete surgeries']));

    otScopeRequest($this, 'post', 'ot.surgeries.cancel', ['scheduled'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $surgery = $this->scheduled->fresh();
    expect($surgery->status)->toBe('cancelled')
        ->and($surgery->cancelled_reason)->toBe('Scoped cancel reason');
});

it('writes no monitoring row when view surgeries posts a monitoring record', function (string $route, string $model) {
    $this->actingAs(otScopeUser(['view surgeries']));
    $before = $model::count();

    $response = otScopeRequest($this, 'post', $route, ['live']);

    expect($model::count())->toBe($before);
    $response->assertForbidden();
})->with([
    'store-anaesthesia → anaesthesia_records' => ['ot.monitoring.store-anaesthesia', AnaesthesiaRecord::class],
    'store-vitals → operative_vitals' => ['ot.monitoring.store-vitals', OperativeVital::class],
    'store-post-op → post_op_monitoring' => ['ot.monitoring.store-post-op', PostOpMonitoring::class],
]);

it('schedules no surgery when view surgeries posts surgeries.store', function () {
    $this->actingAs(otScopeUser(['view surgeries']));
    $before = Surgery::count();

    $response = otScopeRequest($this, 'post', 'ot.surgeries.store', []);

    expect(Surgery::count())->toBe($before)
        ->and(Surgery::where('procedure_name', 'Scoped Hernia Repair')->exists())->toBeFalse();
    $response->assertForbidden();
});

it('creates no theatre when edit surgeries posts theatres.store', function () {
    $this->actingAs(otScopeUser(['edit surgeries']));
    $before = OperationTheatre::count();

    $response = otScopeRequest($this, 'post', 'ot.theatres.store', []);

    expect(OperationTheatre::count())->toBe($before)
        ->and(OperationTheatre::where('name', 'OT Scope New Theatre')->exists())->toBeFalse();
    $response->assertForbidden();
});

it('leaves the theatre name and status unchanged when edit surgeries puts theatres.update', function () {
    $this->actingAs(otScopeUser(['edit surgeries']));

    $response = otScopeRequest($this, 'put', 'ot.theatres.update', ['theatre']);

    $theatre = $this->theatre->fresh();
    expect($theatre->name)->toBe('OT Scope Theatre')
        ->and($theatre->status)->toBe('available');
    $response->assertForbidden();
});

// ── AD-1: after a write, redirect only to a page the user can view (Task 6) ─

dataset('ot write redirects', [
    // method, write route, fixtures, write permission, fallback route, fallback fixtures, target route, target fixtures
    'theatres.store' => ['post', 'ot.theatres.store', [], 'manage theatres', 'ot.theatres.create', [], 'ot.theatres', []],
    'theatres.update' => ['put', 'ot.theatres.update', ['theatre'], 'manage theatres', 'ot.theatres.edit', ['theatre'], 'ot.theatres', []],
    'surgeries.store' => ['post', 'ot.surgeries.store', [], 'create surgeries', 'ot.surgeries.create', [], 'ot.surgeries.index', []],
    'surgeries.update' => ['put', 'ot.surgeries.update', ['scheduled'], 'edit surgeries', 'ot.surgeries.edit', ['scheduled'], 'ot.surgeries.show', ['scheduled']],
    'monitoring.store-anaesthesia' => ['post', 'ot.monitoring.store-anaesthesia', ['live'], 'edit surgeries', 'ot.monitoring.anaesthesia', ['live'], 'ot.surgeries.show', ['live']],
]);

it('redirects an OT write-only holder to the fallback with the success flash', function (string $method, string $route, array $fixtures, string $write, string $fallback, array $fallbackFixtures) {
    $this->actingAs(otScopeUser([$write]));

    otScopeRequest($this, $method, $route, $fixtures)
        ->assertRedirect(route($fallback, otScopeParams($this, $fallbackFixtures)))
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('error')
        ->assertSessionHas('success');
})->with('ot write redirects');

it('redirects an OT write + view surgeries holder to the target with the success flash', function (string $method, string $route, array $fixtures, string $write, string $fallback, array $fallbackFixtures, string $target, array $targetFixtures) {
    $this->actingAs(otScopeUser([$write, 'view surgeries']));

    otScopeRequest($this, $method, $route, $fixtures)
        ->assertRedirect(route($target, otScopeParams($this, $targetFixtures)))
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('error')
        ->assertSessionHas('success');
})->with('ot write redirects');

// ── UI gating: action controls render only for permitted users (Task 7) ───

/** The exact markup of each surgery show-page control, keyed by name. */
function otUiShowControls(Surgery $surgery): array
{
    return [
        'start form' => 'action="'.route('ot.surgeries.start', $surgery).'"',
        'postpone button' => 'id="postpone-btn"',
        'postpone form' => 'action="'.route('ot.surgeries.postpone', $surgery).'"',
        'edit link' => 'href="'.route('ot.surgeries.edit', $surgery).'"',
        'complete button' => 'id="complete-btn"',
        'complete form' => 'action="'.route('ot.surgeries.complete', $surgery).'"',
        'cancel button' => 'id="cancel-btn"',
        'cancel form' => 'action="'.route('ot.surgeries.cancel', $surgery).'"',
        'anaesthesia link' => 'href="'.route('ot.monitoring.anaesthesia', $surgery).'"',
        'vitals link' => 'href="'.route('ot.monitoring.vitals', $surgery).'"',
        'post-op link' => 'href="'.route('ot.monitoring.post-op', $surgery).'"',
    ];
}

function otUiAssertControls($response, Surgery $surgery, array $present, array $absent): void
{
    $controls = otUiShowControls($surgery);

    foreach ($present as $name) {
        $response->assertSee($controls[$name], false);
    }
    foreach ($absent as $name) {
        $response->assertDontSee($controls[$name], false);
    }
}

it('hides every action control on a scheduled surgery from a view surgeries-only user', function () {
    $this->actingAs(otScopeUser(['view surgeries']));

    $response = $this->get(route('ot.surgeries.show', $this->scheduled))->assertOk();

    otUiAssertControls($response, $this->scheduled, [], [
        'start form', 'postpone button', 'postpone form', 'edit link',
        'anaesthesia link', 'vitals link', 'post-op link',
        'cancel button', 'cancel form',
    ]);
});

it('hides complete, cancel and monitoring on an in-progress surgery from a view surgeries-only user', function () {
    $this->actingAs(otScopeUser(['view surgeries']));

    $response = $this->get(route('ot.surgeries.show', $this->live))->assertOk();

    otUiAssertControls($response, $this->live, [], [
        'complete button', 'complete form', 'cancel button', 'cancel form',
        'anaesthesia link', 'vitals link', 'post-op link',
    ]);
});

it('shows start, postpone and edit but not cancel on a scheduled surgery to an edit surgeries holder', function () {
    $this->actingAs(otScopeUser(['view surgeries', 'edit surgeries']));

    $response = $this->get(route('ot.surgeries.show', $this->scheduled))->assertOk();

    otUiAssertControls($response, $this->scheduled,
        ['start form', 'postpone button', 'postpone form', 'edit link'],
        ['cancel button', 'cancel form'],
    );
});

it('shows complete and the monitoring links but not cancel on an in-progress surgery to an edit surgeries holder', function () {
    $this->actingAs(otScopeUser(['view surgeries', 'edit surgeries']));

    $response = $this->get(route('ot.surgeries.show', $this->live))->assertOk();

    otUiAssertControls($response, $this->live,
        ['complete button', 'complete form', 'anaesthesia link', 'vitals link', 'post-op link'],
        ['cancel button', 'cancel form'],
    );
});

it('shows cancel but not start or edit to a delete surgeries holder', function () {
    $this->actingAs(otScopeUser(['view surgeries', 'delete surgeries']));

    $response = $this->get(route('ot.surgeries.show', $this->scheduled))->assertOk();

    otUiAssertControls($response, $this->scheduled,
        ['cancel button', 'cancel form'],
        ['start form', 'edit link', 'postpone button', 'postpone form'],
    );
});

it('shows the calendar Schedule link only with create surgeries', function (array $permissions, bool $visible) {
    $this->actingAs(otScopeUser($permissions));

    $response = $this->get(route('ot.calendar'))->assertOk();
    $link = 'href="'.route('ot.surgeries.create').'"';

    $visible ? $response->assertSee($link, false) : $response->assertDontSee($link, false);
})->with([
    'view only' => [['view surgeries'], false],
    'view + edit' => [['view surgeries', 'edit surgeries'], false],
    'view + create' => [['view surgeries', 'create surgeries'], true],
]);

it('shows Add Theatre and per-theatre Edit only with manage theatres', function (array $permissions, bool $visible) {
    $this->actingAs(otScopeUser($permissions));

    $response = $this->get(route('ot.theatres'))->assertOk();
    $links = [
        'href="'.route('ot.theatres.create').'"',
        'href="'.route('ot.theatres.edit', $this->theatre).'"',
    ];

    foreach ($links as $link) {
        $visible ? $response->assertSee($link, false) : $response->assertDontSee($link, false);
    }
})->with([
    'view only' => [['view surgeries'], false],
    'all four surgery permissions' => [['view surgeries', 'create surgeries', 'edit surgeries', 'delete surgeries'], false],
    'view + manage theatres' => [['view surgeries', 'manage theatres'], true],
]);

it('shows the back-link to the surgery only with view surgeries', function (string $page, string $permission, array $extra, bool $visible) {
    $this->actingAs(otScopeUser([$permission, ...$extra]));

    $url = match ($page) {
        'checklist' => route('ot.checklist.show', $this->scheduled),
        'usage' => route('ot.consumables.usage', $this->scheduled),
        'pac' => route('ot.pac.show', PreAnaesthesiaCheckup::where('surgery_id', $this->scheduled->id)->firstOrFail()),
    };

    $response = $this->get($url)->assertOk();
    $link = 'href="'.route('ot.surgeries.show', $this->scheduled).'"';

    $visible ? $response->assertSee($link, false) : $response->assertDontSee($link, false);
})->with([
    'checklist, manage only' => ['checklist', 'manage surgical checklists', [], false],
    'checklist, + edit surgeries' => ['checklist', 'manage surgical checklists', ['edit surgeries'], false],
    'checklist, + view surgeries' => ['checklist', 'manage surgical checklists', ['view surgeries'], true],
    'usage, manage only' => ['usage', 'manage ot consumables', [], false],
    'usage, + edit surgeries' => ['usage', 'manage ot consumables', ['edit surgeries'], false],
    'usage, + view surgeries' => ['usage', 'manage ot consumables', ['view surgeries'], true],
    'pac, manage only' => ['pac', 'manage pac', [], false],
    'pac, + edit surgeries' => ['pac', 'manage pac', ['edit surgeries'], false],
    'pac, + view surgeries' => ['pac', 'manage pac', ['view surgeries'], true],
]);
