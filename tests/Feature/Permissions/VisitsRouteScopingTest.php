<?php

use App\Models\Doctor;
use App\Models\EmergencyVisit;
use App\Models\IpdCareTeam;
use App\Models\IpdVisit;
use App\Models\OpdVisit;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Support\PermissionRegistry;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
| Visits route scoping (RBAC wave 3 remainder, Task 6)
|--------------------------------------------------------------------------
| Each visits resource route accepts exactly one action permission. There is
| no delete action: the destroy route and `delete visits` permission are gone.
| Assigned-visit scoping and checkPatient are driven by the Spatie permission
| `view assigned visits` plus a linked Doctor profile, never by a role name.
*/

beforeEach(function () {
    config([
        'visits.require_typed_visit_routes' => true,
        'visits.dual_write_enabled' => false,
    ]);

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);
    $this->withoutVite();

    $this->patient = Patient::create([
        'name' => 'Visit Scope Patient',
        'gender' => 'male',
        'age' => 40,
        'phone' => '03001112233',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03001112234',
        'emergency_relation' => 'Sibling',
    ]);

    $this->visit = visitScopeVisit($this->patient, 'opd', visitScopeDoctorFor(null));
});

function visitScopeUser(array $permissions, ?string $role = null): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Visit Scope User',
        'email' => 'visit-scope-user-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    if ($role !== null) {
        $user->assignRole(Role::findOrCreate($role, 'web'));
    }

    $user->givePermissionTo($permissions);

    return $user;
}

function visitScopeDoctorFor(?User $user): Doctor
{
    $email = 'visit-scope-doctor-'.uniqid().'@example.com';

    return Doctor::create([
        'user_id' => $user?->id,
        'name' => 'Visit Scope Doctor '.uniqid(),
        'specialization' => 'Cardiology',
        'qualification' => 'MBBS',
        'phone' => '03003334444',
        'email' => $email,
        'gender' => 'male',
        'experience_years' => 8,
        'consultation_fee' => 1500,
        'available_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
        'shift_start' => '09:00',
        'shift_end' => '17:00',
        'status' => 'active',
    ]);
}

function visitScopeVisit(Patient $patient, string $type, ?Doctor $doctor = null, string $status = 'registered'): Visit
{
    $visit = Visit::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor?->id,
        'visit_type' => $type,
        'status' => $status,
        'visit_datetime' => now(),
    ]);

    match ($type) {
        'opd' => OpdVisit::create(['visit_id' => $visit->id, 'queue_priority' => 'medium']),
        'ipd' => IpdVisit::create(['visit_id' => $visit->id]),
        'emergency' => EmergencyVisit::create(['visit_id' => $visit->id]),
        default => null,
    };

    return $visit;
}

function visitScopePayload(string $route, Visit $visit): array
{
    return match ($route) {
        'visits.store' => [
            'patient_id' => $visit->patient_id,
            'visit_type' => 'opd',
            'visit_datetime' => now()->format('Y-m-d H:i'),
        ],
        'visits.update' => [
            'patient_id' => $visit->patient_id,
            'doctor_id' => $visit->doctor_id,
            'visit_datetime' => now()->format('Y-m-d H:i'),
            'status' => 'registered',
            'priority' => 'medium',
        ],
        default => [],
    };
}

function visitScopeUrl(string $route, Visit $visit): string
{
    return match ($route) {
        'visits.index' => route($route, ['visit_type' => 'opd']),
        'visits.create' => route($route, ['visit_type' => 'opd']),
        'visits.data' => route($route, ['visit_type' => 'opd', 'draw' => 1, 'start' => 0, 'length' => 10]),
        'visits.show', 'visits.edit', 'visits.update' => route($route, ['visit' => $visit->id]),
        default => route($route),
    };
}

function visitScopeRequest($test, string $method, string $route, Visit $visit, array $extra = [])
{
    $url = visitScopeUrl($route, $visit);

    return $method === 'get'
        ? $test->get($url)
        : $test->{$method}($url, array_merge(visitScopePayload($route, $visit), $extra));
}

function visitScopeData($test, array $extra = []): array
{
    $response = $test->getJson(route('visits.data', array_merge([
        'visit_type' => 'opd',
        'draw' => 1,
        'start' => 0,
        'length' => 100,
        'search' => ['value' => ''],
    ], $extra)));

    $response->assertOk();

    return collect($response->json('data'))->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
}

// ── 1. Deny: wrong-verb holder must get 403 ──────────────────────────────

dataset('visits escalations', [
    'index via create only' => ['get', 'visits.index', ['create visits']],
    'index via edit only' => ['get', 'visits.index', ['edit visits']],
    'index via assigned only' => ['get', 'visits.index', ['view assigned visits']],
    'data via create only' => ['get', 'visits.data', ['create visits']],
    'data via edit only' => ['get', 'visits.data', ['edit visits']],
    'show via create only' => ['get', 'visits.show', ['create visits']],
    'show via edit only' => ['get', 'visits.show', ['edit visits']],
    'create via view only' => ['get', 'visits.create', ['view visits']],
    'create via edit only' => ['get', 'visits.create', ['edit visits']],
    'store via view only' => ['post', 'visits.store', ['view visits']],
    'store via edit only' => ['post', 'visits.store', ['edit visits']],
    'edit via view only' => ['get', 'visits.edit', ['view visits']],
    'edit via create only' => ['get', 'visits.edit', ['create visits']],
    'update via view only' => ['put', 'visits.update', ['view visits']],
    'update via create only' => ['put', 'visits.update', ['create visits']],
]);

it('forbids a wrong-verb permission on each visits route', function (string $method, string $route, array $permissions) {
    $this->actingAs(visitScopeUser($permissions));

    visitScopeRequest($this, $method, $route, $this->visit)->assertForbidden();
})->with('visits escalations');

// ── 2. Positive control: exactly the matching permission passes ──────────

dataset('visits positive controls', [
    'index' => ['get', 'visits.index', 'view visits'],
    'data' => ['get', 'visits.data', 'view visits'],
    'show' => ['get', 'visits.show', 'view visits'],
    'create' => ['get', 'visits.create', 'create visits'],
    'store' => ['post', 'visits.store', 'create visits'],
    'edit' => ['get', 'visits.edit', 'edit visits'],
    'update' => ['put', 'visits.update', 'edit visits'],
]);

it('allows exactly the matching permission on each visits route', function (string $method, string $route, string $permission) {
    $this->actingAs(visitScopeUser([$permission]));

    $response = visitScopeRequest($this, $method, $route, $this->visit);

    expect($response->getStatusCode())->not->toBe(403)->not->toBe(404);

    if ($method === 'get') {
        $response->assertOk();

        return;
    }

    $response->assertRedirect()->assertSessionHasNoErrors();
})->with('visits positive controls');

// ── 3. Side effects: a blocked write writes nothing ─────────────────────

it('creates no visit when view visits posts store', function () {
    $this->actingAs(visitScopeUser(['view visits']));
    $count = Visit::count();

    visitScopeRequest($this, 'post', 'visits.store', $this->visit)->assertForbidden();

    expect(Visit::count())->toBe($count);
});

it('leaves the visit unchanged when view visits puts update', function () {
    $this->actingAs(visitScopeUser(['view visits']));

    visitScopeRequest($this, 'put', 'visits.update', $this->visit, ['status' => 'completed'])->assertForbidden();

    expect($this->visit->fresh()->status)->toBe('registered');
});

// ── 4. No delete: route, permission and registry entry are gone ──────────

it('does not register a visits destroy route', function () {
    expect(Route::has('visits.destroy'))->toBeFalse();
});

it('has no DELETE route for a visit URL', function () {
    $this->actingAs(visitScopeUser(['view visits', 'create visits', 'edit visits']));

    $this->delete(url('visits/'.$this->visit->id))->assertStatus(405);

    expect(Visit::find($this->visit->id))->not->toBeNull();
});

it('does not define destroy on the visit controller', function () {
    expect(method_exists(\App\Http\Controllers\VisitController::class, 'destroy'))->toBeFalse();
});

it('no longer lists delete visits in the permission registry and adds view assigned visits', function () {
    expect(PermissionRegistry::all())
        ->not->toContain('delete visits')
        ->toContain('view assigned visits');
});

it('forbids a user with only delete visits on every visits route', function () {
    $this->actingAs(visitScopeUser(['delete visits']));

    foreach ([
        ['get', 'visits.index'],
        ['get', 'visits.data'],
        ['get', 'visits.create'],
        ['post', 'visits.store'],
        ['get', 'visits.show'],
        ['get', 'visits.edit'],
        ['put', 'visits.update'],
    ] as [$method, $route]) {
        visitScopeRequest($this, $method, $route, $this->visit)->assertForbidden();
    }
});

// ── 5. Assigned scoping uses Spatie + Doctor profile, not a role name ────

it('scopes the data list to own and care-team visits for view assigned visits plus a Doctor profile', function () {
    $user = visitScopeUser(['view visits', 'view assigned visits']);
    $doctor = visitScopeDoctorFor($user);
    $other = visitScopeDoctorFor(null);

    $own = visitScopeVisit($this->patient, 'opd', $doctor);
    $theirs = visitScopeVisit($this->patient, 'opd', $other);
    $unassigned = visitScopeVisit($this->patient, 'opd');

    $this->actingAs($user);

    expect(visitScopeData($this))->toBe([$own->id]);
    expect([$theirs->id, $unassigned->id])->not->toContain($own->id);
});

it('includes ipd visits where the doctor is on the care team', function () {
    $user = visitScopeUser(['view visits', 'view assigned visits']);
    $doctor = visitScopeDoctorFor($user);
    $other = visitScopeDoctorFor(null);

    $teamVisit = visitScopeVisit($this->patient, 'ipd');
    IpdCareTeam::create([
        'visit_id' => $teamVisit->id,
        'doctor_id' => $doctor->id,
        'is_primary' => true,
        'added_at' => now(),
    ]);
    $otherTeamVisit = visitScopeVisit($this->patient, 'ipd');
    IpdCareTeam::create([
        'visit_id' => $otherTeamVisit->id,
        'doctor_id' => $other->id,
        'is_primary' => true,
        'added_at' => now(),
    ]);

    $this->actingAs($user);

    expect(visitScopeData($this, ['visit_type' => 'ipd']))->toBe([$teamVisit->id]);
});

it('keeps the unscoped list for view visits without view assigned visits', function () {
    $other = visitScopeDoctorFor(null);
    $assigned = visitScopeVisit($this->patient, 'opd', $other);

    $this->actingAs(visitScopeUser(['view visits']));

    expect(visitScopeData($this))->toBe(collect([$this->visit->id, $assigned->id])->sort()->values()->all());
});

it('keeps the unscoped list when view assigned visits has no Doctor profile', function () {
    $other = visitScopeDoctorFor(null);
    $assigned = visitScopeVisit($this->patient, 'opd', $other);

    $this->actingAs(visitScopeUser(['view visits', 'view assigned visits']));

    expect(visitScopeData($this))->toBe(collect([$this->visit->id, $assigned->id])->sort()->values()->all());
});

it('does not scope a role named Doctor that lacks view assigned visits', function () {
    $user = visitScopeUser(['view visits'], 'Doctor');
    $doctor = visitScopeDoctorFor($user);
    $other = visitScopeDoctorFor(null);
    visitScopeVisit($this->patient, 'opd', $doctor);
    $theirs = visitScopeVisit($this->patient, 'opd', $other);

    $this->actingAs($user);

    expect(visitScopeData($this))->toContain($theirs->id);
});

it('scopes by permission even when the user has no Doctor role', function () {
    $user = visitScopeUser(['view visits', 'view assigned visits']);
    $doctor = visitScopeDoctorFor($user);
    $own = visitScopeVisit($this->patient, 'opd', $doctor);

    $this->actingAs($user);

    expect($user->hasRole('Doctor'))->toBeFalse()
        ->and(visitScopeData($this))->toBe([$own->id]);
});

// ── 6. checkPatient: Spatie + Doctor profile + assignment ────────────────

it('forbids checkPatient without view assigned visits even for a role named Doctor with a profile and assignment', function () {
    $user = visitScopeUser(['edit visits'], 'Doctor');
    $doctor = visitScopeDoctorFor($user);
    $visit = visitScopeVisit($this->patient, 'opd', $doctor, 'with_doctor');

    $this->actingAs($user)
        ->post(route('visits.check-patient', $visit))
        ->assertForbidden();

    expect($visit->fresh()->status)->toBe('with_doctor');
});

it('forbids checkPatient with view assigned visits but no Doctor profile', function () {
    $other = visitScopeDoctorFor(null);
    $visit = visitScopeVisit($this->patient, 'opd', $other, 'with_doctor');

    $this->actingAs(visitScopeUser(['edit visits', 'view assigned visits']))
        ->post(route('visits.check-patient', $visit))
        ->assertForbidden();

    expect($visit->fresh()->status)->toBe('with_doctor');
});

it('forbids checkPatient for a visit assigned to another doctor', function () {
    $user = visitScopeUser(['edit visits', 'view assigned visits']);
    visitScopeDoctorFor($user);
    $other = visitScopeDoctorFor(null);
    $visit = visitScopeVisit($this->patient, 'opd', $other, 'with_doctor');

    $this->actingAs($user)
        ->post(route('visits.check-patient', $visit))
        ->assertForbidden();

    expect($visit->fresh()->status)->toBe('with_doctor');
});

it('allows checkPatient with view assigned visits, a Doctor profile and assignment', function () {
    $user = visitScopeUser(['edit visits', 'view assigned visits']);
    $doctor = visitScopeDoctorFor($user);
    $visit = visitScopeVisit($this->patient, 'opd', $doctor, 'with_doctor');

    $response = $this->actingAs($user)->post(route('visits.check-patient', $visit));

    expect($response->getStatusCode())->not->toBe(403);
    expect($visit->fresh()->status)->toBe('completed');
});

it('allows checkPatient for an ipd care-team member holding view assigned visits', function () {
    $user = visitScopeUser(['edit visits', 'view assigned visits']);
    $doctor = visitScopeDoctorFor($user);
    $visit = visitScopeVisit($this->patient, 'ipd', null, 'admitted');
    IpdCareTeam::create([
        'visit_id' => $visit->id,
        'doctor_id' => $doctor->id,
        'is_primary' => true,
        'added_at' => now(),
    ]);

    $response = $this->actingAs($user)->post(route('visits.check-patient', $visit));

    expect($response->getStatusCode())->not->toBe(403);
});

it('keeps check-patient behind edit visits', function () {
    $user = visitScopeUser(['view assigned visits', 'view visits']);
    $doctor = visitScopeDoctorFor($user);
    $visit = visitScopeVisit($this->patient, 'opd', $doctor, 'with_doctor');

    $this->actingAs($user)
        ->post(route('visits.check-patient', $visit))
        ->assertForbidden();
});

// ── 7. UI gating ─────────────────────────────────────────────────────────

it('shows no visit Delete control on the visits index', function () {
    $this->actingAs(visitScopeUser(['view visits', 'create visits', 'edit visits']));

    $this->get(route('visits.index', ['visit_type' => 'opd']))
        ->assertOk()
        ->assertDontSee('delete visits', false)
        ->assertDontSee('name="_method" value="DELETE"', false);
});
