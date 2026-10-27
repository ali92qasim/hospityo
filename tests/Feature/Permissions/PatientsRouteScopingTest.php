<?php

use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

/*
|--------------------------------------------------------------------------
| Patients route scoping (RBAC wave 3, Task 4 — RED)
|--------------------------------------------------------------------------
| Each Patients HTTP route accepts exactly one action permission (D6), deletes
| are soft, and restore is a separate permission. Written before the route /
| controller / view fixes (Task 5 / Task 6), so many cases fail until then.
|
| trashed / restore URLs are literal paths (not route()) so failures before
| Task 5 are 404 / wrong status rather than RouteNotFoundException.
*/

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
    $this->withoutVite();

    $this->artisan('migrate', [
        '--path' => 'database/migrations/tenant',
        '--database' => 'tenant',
    ]);

    patScopeTenant();

    $this->patient = patScopePatient();
});

function patScopeTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => $module === 'patients');

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function patScopeUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Patients Scope User',
        'email' => 'pat-scope-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

function patScopePatient(): Patient
{
    return Patient::create([
        'name' => 'Pat Scope Fixture',
        'gender' => 'male',
        'age' => 30,
        'phone' => '03001234567',
    ]);
}

function patScopePayload(string $route): array
{
    return match ($route) {
        'patients.store', 'patients.update' => [
            'name' => 'Pat Scope Name',
            'gender' => 'female',
            'age' => 25,
            'phone' => '03007654321',
            'marital_status' => 'single',
        ],
        default => [],
    };
}

/** Routes whose URL needs the fixture patient. */
function patScopeNeedsPatient(string $route): bool
{
    return in_array($route, [
        'patients.show', 'patients.edit', 'patients.update', 'patients.destroy',
        'patients.history', 'patients.restore',
    ], true);
}

function patScopeUrl(string $route, ?Patient $patient = null): string
{
    return match ($route) {
        'patients.trashed' => url('patients/trashed'),
        'patients.restore' => url('patients/'.$patient->id.'/restore'),
        'api.patients.search' => route('api.patients.search', ['phone' => '03001']),
        default => route($route, $patient ? ['patient' => $patient->id] : []),
    };
}

function patScopeRequest($test, string $method, string $route, ?Patient $patient = null, array $extra = [])
{
    $url = patScopeUrl($route, $patient);

    return $method === 'get'
        ? $test->get($url)
        : $test->{$method}($url, array_merge(patScopePayload($route), $extra));
}

/** Trash the fixture so restore / trashed cases have a soft-deleted row. */
function patScopeTrash(Patient $patient): Patient
{
    $patient->delete();

    return $patient;
}

// ── 0. New routes exist (red until Task 5) ───────────────────────────────

it('registers the trashed and restore routes', function () {
    expect(Route::has('patients.trashed'))->toBeTrue()
        ->and(Route::has('patients.restore'))->toBeTrue();
});

// ── 1. Deny: wrong-verb holder must get 403 ──────────────────────────────

dataset('patients escalations', [
    'index via create only' => ['get', 'patients.index', ['create patients']],
    'data via create only' => ['get', 'patients.data', ['create patients']],
    'show via create only' => ['get', 'patients.show', ['create patients']],
    'history via create only' => ['get', 'patients.history', ['create patients']],
    'search via create only' => ['get', 'api.patients.search', ['create patients']],
    'search via edit only' => ['get', 'api.patients.search', ['edit patients']],
    'create via view only' => ['get', 'patients.create', ['view patients']],
    'store via view only' => ['post', 'patients.store', ['view patients']],
    'edit via view only' => ['get', 'patients.edit', ['view patients']],
    'update via view only' => ['put', 'patients.update', ['view patients']],
    'destroy via view only' => ['delete', 'patients.destroy', ['view patients']],
    'trashed via view only' => ['get', 'patients.trashed', ['view patients']],
    'restore via view only' => ['post', 'patients.restore', ['view patients']],
    'store via edit only' => ['post', 'patients.store', ['edit patients']],
    'destroy via edit only' => ['delete', 'patients.destroy', ['edit patients']],
    'destroy via create only' => ['delete', 'patients.destroy', ['create patients']],
    'trashed via edit only' => ['get', 'patients.trashed', ['edit patients']],
    'restore via delete only' => ['post', 'patients.restore', ['delete patients']],
    'restore via HA-shaped' => ['post', 'patients.restore', ['view patients', 'create patients', 'edit patients', 'delete patients']],
]);

it('forbids a wrong-verb permission on each patients route', function (string $method, string $route, array $permissions) {
    $this->actingAs(patScopeUser($permissions));

    if ($route === 'patients.restore' || $route === 'patients.trashed') {
        patScopeTrash($this->patient);
    }

    $patient = patScopeNeedsPatient($route) ? $this->patient : null;

    patScopeRequest($this, $method, $route, $patient)->assertForbidden();
})->with('patients escalations');

// ── 2. Positive control: exactly the new permission passes ───────────────

dataset('patients positive controls', [
    'index' => ['get', 'patients.index', 'view patients'],
    'data' => ['get', 'patients.data', 'view patients'],
    'show' => ['get', 'patients.show', 'view patients'],
    'history' => ['get', 'patients.history', 'view patients'],
    'search' => ['get', 'api.patients.search', 'view patients'],
    'create' => ['get', 'patients.create', 'create patients'],
    'store' => ['post', 'patients.store', 'create patients'],
    'edit' => ['get', 'patients.edit', 'edit patients'],
    'update' => ['put', 'patients.update', 'edit patients'],
    'destroy' => ['delete', 'patients.destroy', 'delete patients'],
    'trashed' => ['get', 'patients.trashed', 'delete patients'],
    'restore' => ['post', 'patients.restore', 'restore patients'],
]);

it('allows exactly the new permission on each patients route', function (string $method, string $route, string $permission) {
    $this->actingAs(patScopeUser([$permission]));

    if ($route === 'patients.restore' || $route === 'patients.trashed') {
        patScopeTrash($this->patient);
    }

    $patient = patScopeNeedsPatient($route) ? $this->patient : null;

    $response = patScopeRequest($this, $method, $route, $patient);

    expect($response->getStatusCode())->not->toBe(403)->not->toBe(404);

    if ($method === 'get') {
        $response->assertOk();

        return;
    }

    $response->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('error')
        ->assertSessionHas('success');
})->with('patients positive controls');

// ── 3. Side effects: a blocked write writes nothing ──────────────────────

it('creates no patient when view patients posts store', function () {
    $this->actingAs(patScopeUser(['view patients']));
    $before = Patient::withTrashed()->count();

    $response = patScopeRequest($this, 'post', 'patients.store');

    expect(Patient::withTrashed()->count())->toBe($before)
        ->and(Patient::withTrashed()->where('name', 'Pat Scope Name')->exists())->toBeFalse();
    $response->assertForbidden();
});

it('leaves the patient name unchanged when view patients puts update', function () {
    $this->actingAs(patScopeUser(['view patients']));

    $response = patScopeRequest($this, 'put', 'patients.update', $this->patient);

    expect($this->patient->fresh()->name)->toBe('Pat Scope Fixture');
    $response->assertForbidden();
});

it('does not soft-delete the patient when view patients deletes destroy', function () {
    $this->actingAs(patScopeUser(['view patients']));

    $response = patScopeRequest($this, 'delete', 'patients.destroy', $this->patient);

    expect(Patient::withTrashed()->find($this->patient->id)->deleted_at)->toBeNull();
    $response->assertForbidden();
});

it('does not soft-delete the patient when edit patients deletes destroy', function () {
    $this->actingAs(patScopeUser(['edit patients']));

    $response = patScopeRequest($this, 'delete', 'patients.destroy', $this->patient);

    expect(Patient::withTrashed()->find($this->patient->id)->deleted_at)->toBeNull();
    $response->assertForbidden();
});

it('creates no patient when edit patients posts store', function () {
    $this->actingAs(patScopeUser(['edit patients']));
    $before = Patient::withTrashed()->count();

    $response = patScopeRequest($this, 'post', 'patients.store');

    expect(Patient::withTrashed()->count())->toBe($before);
    $response->assertForbidden();
});

// ── 4. Soft delete ───────────────────────────────────────────────────────

it('soft-deletes via destroy and moves the patient from index data and search to trashed', function () {
    $this->actingAs(patScopeUser(['view patients', 'delete patients']));
    $id = $this->patient->id;

    patScopeRequest($this, 'delete', 'patients.destroy', $this->patient)
        ->assertRedirect()
        ->assertSessionHas('success');

    $row = Patient::withTrashed()->find($id);
    expect($row)->not->toBeNull()
        ->and($row->deleted_at)->not->toBeNull()
        ->and(Patient::find($id))->toBeNull();

    $data = patScopeRequest($this, 'get', 'patients.data');
    $data->assertOk();
    expect($data->getContent())->not->toContain('Pat Scope Fixture');

    $search = patScopeRequest($this, 'get', 'api.patients.search');
    $search->assertOk()->assertJson(['found' => false]);

    $trashed = patScopeRequest($this, 'get', 'patients.trashed');
    $trashed->assertOk()->assertSee('Pat Scope Fixture');
});

it('shows a live patient in data and search but not on trashed', function () {
    $this->actingAs(patScopeUser(['view patients', 'delete patients']));

    $data = patScopeRequest($this, 'get', 'patients.data');
    $data->assertOk();
    expect($data->getContent())->toContain('Pat Scope Fixture');

    patScopeRequest($this, 'get', 'api.patients.search')
        ->assertOk()
        ->assertJson(['found' => true, 'patient' => ['id' => $this->patient->id]]);

    patScopeRequest($this, 'get', 'patients.trashed')
        ->assertOk()
        ->assertDontSee('Pat Scope Fixture');
});

it('never force-deletes on destroy', function () {
    $this->actingAs(patScopeUser(['delete patients']));
    $id = $this->patient->id;

    patScopeRequest($this, 'delete', 'patients.destroy', $this->patient)->assertRedirect();

    expect(Patient::withTrashed()->find($id))->not->toBeNull();
});

// ── 5. HA-shaped user: destroy + trashed ok, restore forbidden ───────────

it('lets an HA-shaped user destroy and open trashed but forbids restore', function () {
    $ha = patScopeUser(['view patients', 'create patients', 'edit patients', 'delete patients']);
    $this->actingAs($ha);
    $id = $this->patient->id;

    patScopeRequest($this, 'delete', 'patients.destroy', $this->patient)
        ->assertRedirect()
        ->assertSessionHas('success');

    patScopeRequest($this, 'get', 'patients.trashed')
        ->assertOk()
        ->assertSee('Pat Scope Fixture');

    patScopeRequest($this, 'post', 'patients.restore', $this->patient)->assertForbidden();

    expect(Patient::withTrashed()->find($id)->deleted_at)->not->toBeNull()
        ->and(Patient::find($id))->toBeNull();
});

// ── 6. Restore holder ────────────────────────────────────────────────────

it('restores a trashed patient and returns it to index data', function () {
    $this->actingAs(patScopeUser(['view patients', 'delete patients', 'restore patients']));
    $id = $this->patient->id;
    patScopeTrash($this->patient);

    patScopeRequest($this, 'post', 'patients.restore', $this->patient)
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Patient::find($id))->not->toBeNull()
        ->and(Patient::withTrashed()->find($id)->deleted_at)->toBeNull();

    $data = patScopeRequest($this, 'get', 'patients.data');
    $data->assertOk();
    expect($data->getContent())->toContain('Pat Scope Fixture');

    $this->get(route('patients.index'))->assertOk();
});

// ── 7. AD-1 redirects ────────────────────────────────────────────────────

it('redirects a write-only create holder after store to create with the success flash', function () {
    $this->actingAs(patScopeUser(['create patients']));

    patScopeRequest($this, 'post', 'patients.store')
        ->assertRedirect(route('patients.create'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    expect(Patient::where('name', 'Pat Scope Name')->exists())->toBeTrue();
});

it('redirects create + view after store to the index with the success flash', function () {
    $this->actingAs(patScopeUser(['create patients', 'view patients']));

    patScopeRequest($this, 'post', 'patients.store')
        ->assertRedirect(route('patients.index'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');
});

it('keeps save-and-add-another on create even with view patients', function () {
    $this->actingAs(patScopeUser(['create patients', 'view patients']));

    patScopeRequest($this, 'post', 'patients.store', null, ['save_and_add_another' => '1'])
        ->assertRedirect(route('patients.create'))
        ->assertSessionHas('success');
});

it('redirects an edit-only holder after update to edit with the success flash', function () {
    $this->actingAs(patScopeUser(['edit patients']));

    patScopeRequest($this, 'put', 'patients.update', $this->patient)
        ->assertRedirect(route('patients.edit', $this->patient))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');
});

it('redirects edit + view after update to the index with the success flash', function () {
    $this->actingAs(patScopeUser(['edit patients', 'view patients']));

    patScopeRequest($this, 'put', 'patients.update', $this->patient)
        ->assertRedirect(route('patients.index'))
        ->assertSessionHas('success');
});

it('redirects a delete-only holder after destroy back with the success flash', function () {
    $this->actingAs(patScopeUser(['delete patients']));
    $previous = url('/dashboard?from=pat-scope-destroy');
    $this->from($previous);

    patScopeRequest($this, 'delete', 'patients.destroy', $this->patient)
        ->assertRedirect($previous)
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');
});

it('redirects delete + view after destroy to the index with the success flash', function () {
    $this->actingAs(patScopeUser(['delete patients', 'view patients']));
    $this->from(url('/dashboard?from=pat-scope-destroy'));

    patScopeRequest($this, 'delete', 'patients.destroy', $this->patient)
        ->assertRedirect(route('patients.index'))
        ->assertSessionHas('success');
});

it('redirects a restore-only holder after restore to trashed with the success flash', function () {
    $this->actingAs(patScopeUser(['restore patients']));
    patScopeTrash($this->patient);

    patScopeRequest($this, 'post', 'patients.restore', $this->patient)
        ->assertRedirect(url('patients/trashed'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');
});

it('redirects restore + view after restore to the index with the success flash', function () {
    $this->actingAs(patScopeUser(['restore patients', 'view patients']));
    patScopeTrash($this->patient);

    patScopeRequest($this, 'post', 'patients.restore', $this->patient)
        ->assertRedirect(route('patients.index'))
        ->assertSessionHas('success');
});

// ── 8. UI gating (Blade / JS flags — Task 6) ─────────────────────────────

it('hides Add New, edit/delete flags and trash link from a view-only user on the index', function () {
    $this->actingAs(patScopeUser(['view patients']));

    $this->get(route('patients.index'))
        ->assertOk()
        ->assertDontSee('Add New Patient')
        ->assertDontSee(e(route('patients.create')), false)
        ->assertDontSee('data-can-edit', false)
        ->assertDontSee('data-can-delete', false)
        ->assertDontSee('patients/trashed', false);
});

it('shows Add New Patient to create patients holders', function () {
    $this->actingAs(patScopeUser(['view patients', 'create patients']));

    $this->get(route('patients.index'))
        ->assertOk()
        ->assertSee('Add New Patient')
        ->assertSee(e(route('patients.create')), false)
        ->assertDontSee('data-can-edit', false)
        ->assertDontSee('data-can-delete', false)
        ->assertDontSee('patients/trashed', false);
});

it('shows data-can-edit to edit patients holders only', function () {
    $this->actingAs(patScopeUser(['view patients', 'edit patients']));

    $this->get(route('patients.index'))
        ->assertOk()
        ->assertSee('data-can-edit="1"', false)
        ->assertDontSee('data-can-delete', false)
        ->assertDontSee('Add New Patient');
});

it('shows the trash link and data-can-delete to delete patients holders', function () {
    $this->actingAs(patScopeUser(['view patients', 'delete patients']));

    $this->get(route('patients.index'))
        ->assertOk()
        ->assertSee('data-can-delete="1"', false)
        ->assertSee(e(url('patients/trashed')), false)
        ->assertDontSee('data-can-edit', false)
        ->assertDontSee('Add New Patient');
});

it('hides Edit and delete from a view-only user on show', function () {
    $this->actingAs(patScopeUser(['view patients']));

    $this->get(route('patients.show', $this->patient))
        ->assertOk()
        ->assertDontSee(e(route('patients.edit', $this->patient)), false)
        ->assertDontSee(e(route('patients.destroy', $this->patient)), false);
});

it('shows Edit on show for edit patients holders', function () {
    $this->actingAs(patScopeUser(['view patients', 'edit patients']));

    $this->get(route('patients.show', $this->patient))
        ->assertOk()
        ->assertSee(e(route('patients.edit', $this->patient)), false);
});

it('shows soft-delete on show for delete patients holders', function () {
    $this->actingAs(patScopeUser(['view patients', 'delete patients']));

    $this->get(route('patients.show', $this->patient))
        ->assertOk()
        ->assertSee('action="'.e(route('patients.destroy', $this->patient)).'"', false);
});

it('shows Restore on the trashed page to restore patients holders', function () {
    $this->actingAs(patScopeUser(['delete patients', 'restore patients']));
    patScopeTrash($this->patient);

    $this->get(url('patients/trashed'))
        ->assertOk()
        ->assertSee('Pat Scope Fixture')
        ->assertSee('Restore')
        ->assertSee('action="'.e(url('patients/'.$this->patient->id.'/restore')).'"', false);
});

it('hides Restore on the trashed page from delete-only holders', function () {
    $this->actingAs(patScopeUser(['delete patients']));
    patScopeTrash($this->patient);

    $this->get(url('patients/trashed'))
        ->assertOk()
        ->assertSee('Pat Scope Fixture')
        ->assertDontSee('Restore')
        ->assertDontSee(e(url('patients/'.$this->patient->id.'/restore')), false);
});
