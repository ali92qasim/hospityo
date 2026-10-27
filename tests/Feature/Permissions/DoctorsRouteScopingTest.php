<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

/*
|--------------------------------------------------------------------------
| Doctors route scoping (RBAC wave 3 doctors, Task 4 — RED)
|--------------------------------------------------------------------------
| Each admin Doctors HTTP route accepts exactly one action permission (D6),
| except doctors.email-available (create|edit). Deletes are soft: the doctor
| is deactivated, the linked User row stays, and the TenantUser login mapping
| is removed. Restore is Super Admin only (`restore doctors`). The assignments
| page is gated by the Spatie permission `view doctor assignments`, never by
| a role name.
|
| Written before the route / controller / view fixes (Tasks 5-7), so many
| cases fail until then.
|
| trashed / restore URLs are literal paths (not route()) so failures before
| the routes exist are 404 / wrong status rather than RouteNotFoundException.
*/

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
    $this->withoutVite();

    // TenantUser lives on the landlord connection; give it a throwaway DB.
    config([
        'database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ],
    ]);
    $this->app['db']->purge('landlord');

    $this->artisan('migrate', [
        '--path' => 'database/migrations/landlord',
        '--database' => 'landlord',
    ]);
    $this->artisan('migrate', [
        '--path' => 'database/migrations/tenant',
        '--database' => 'tenant',
    ]);

    docScopeTenant();

    $this->doctor = docScopeDoctor();
});

function docScopeTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => $module === 'doctors');

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function docScopeUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Doctors Scope User',
        'email' => 'doc-scope-user-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

/** A doctor with a linked login user and a TenantUser mapping. */
function docScopeDoctor(): Doctor
{
    $email = 'doc-scope-fixture-'.uniqid().'@example.com';

    $user = User::create([
        'name' => 'Doc Scope Fixture',
        'email' => $email,
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    TenantUser::register($email, 1);

    return Doctor::create([
        'user_id' => $user->id,
        'name' => 'Doc Scope Fixture',
        'specialization' => 'Cardiology',
        'qualification' => 'MBBS',
        'phone' => '03001234567',
        'email' => $email,
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00',
        'shift_end' => '17:00',
        'status' => 'active',
    ]);
}

function docScopeDepartment(): Department
{
    return Department::firstOrCreate(
        ['code' => 'DOCSCOPE'],
        ['name' => 'Doc Scope Dept', 'status' => 'active'],
    );
}

function docScopePayload(string $route): array
{
    return match ($route) {
        'doctors.store', 'doctors.update' => [
            'name' => 'Doc Scope Name',
            'specialization' => 'Neurology',
            'qualification' => 'MBBS, FCPS',
            'phone' => '03007654321',
            'email' => 'doc-scope-payload-'.$route.'@example.com',
            'gender' => 'female',
            'experience_years' => 8,
            'consultation_fee' => 1500,
            'department_id' => docScopeDepartment()->id,
            'shift_start' => '08:00',
            'shift_end' => '16:00',
            'status' => 'active',
        ],
        default => [],
    };
}

/** Routes whose URL needs the fixture doctor. */
function docScopeNeedsDoctor(string $route): bool
{
    return in_array($route, [
        'doctors.show', 'doctors.edit', 'doctors.update', 'doctors.destroy',
        'doctors.restore',
    ], true);
}

function docScopeUrl(string $route, ?Doctor $doctor = null): string
{
    return match ($route) {
        'doctors.trashed' => url('doctors/trashed'),
        'doctors.restore' => url('doctors/'.$doctor->id.'/restore'),
        'doctors.email-available' => route('doctors.email-available', ['email' => 'free-'.uniqid().'@example.com']),
        'doctor.assignments' => route('doctor.assignments'),
        default => route($route, $doctor ? ['doctor' => $doctor->id] : []),
    };
}

function docScopeRequest($test, string $method, string $route, ?Doctor $doctor = null, array $extra = [])
{
    $url = docScopeUrl($route, $doctor);

    return $method === 'get'
        ? $test->get($url)
        : $test->{$method}($url, array_merge(docScopePayload($route), $extra));
}

/** Mimic the destroy end state so restore / trashed cases have a trashed row. */
function docScopeTrash(Doctor $doctor): Doctor
{
    $doctor->delete();
    Doctor::withTrashed()->whereKey($doctor->id)->update(['status' => 'inactive']);
    TenantUser::unregister($doctor->email, 1);

    return $doctor;
}

// ── 0. New routes exist (red until Task 5) ───────────────────────────────

it('registers the trashed and restore routes', function () {
    expect(Route::has('doctors.trashed'))->toBeTrue()
        ->and(Route::has('doctors.restore'))->toBeTrue();
});

// ── 1. Deny: wrong-verb holder must get 403 ──────────────────────────────

dataset('doctors escalations', [
    'index via create only' => ['get', 'doctors.index', ['create doctors']],
    'index via delete only' => ['get', 'doctors.index', ['delete doctors']],
    'data via create only' => ['get', 'doctors.data', ['create doctors']],
    'data via edit only' => ['get', 'doctors.data', ['edit doctors']],
    'data via delete only' => ['get', 'doctors.data', ['delete doctors']],
    'show via create only' => ['get', 'doctors.show', ['create doctors']],
    'show via edit only' => ['get', 'doctors.show', ['edit doctors']],
    'show via delete only' => ['get', 'doctors.show', ['delete doctors']],
    'create via view only' => ['get', 'doctors.create', ['view doctors']],
    'create via edit only' => ['get', 'doctors.create', ['edit doctors']],
    'store via view only' => ['post', 'doctors.store', ['view doctors']],
    'store via edit only' => ['post', 'doctors.store', ['edit doctors']],
    'store via delete only' => ['post', 'doctors.store', ['delete doctors']],
    'edit via view only' => ['get', 'doctors.edit', ['view doctors']],
    'edit via create only' => ['get', 'doctors.edit', ['create doctors']],
    'update via view only' => ['put', 'doctors.update', ['view doctors']],
    'update via create only' => ['put', 'doctors.update', ['create doctors']],
    'destroy via view only' => ['delete', 'doctors.destroy', ['view doctors']],
    'destroy via create only' => ['delete', 'doctors.destroy', ['create doctors']],
    'destroy via edit only' => ['delete', 'doctors.destroy', ['edit doctors']],
    'email-available via view only' => ['get', 'doctors.email-available', ['view doctors']],
    'email-available via delete only' => ['get', 'doctors.email-available', ['delete doctors']],
    'trashed via view only' => ['get', 'doctors.trashed', ['view doctors']],
    'trashed via delete only' => ['get', 'doctors.trashed', ['delete doctors']],
    'trashed via edit only' => ['get', 'doctors.trashed', ['edit doctors']],
    'trashed via HA-shaped' => ['get', 'doctors.trashed', ['view doctors', 'create doctors', 'edit doctors', 'delete doctors']],
    'restore via view only' => ['post', 'doctors.restore', ['view doctors']],
    'restore via delete only' => ['post', 'doctors.restore', ['delete doctors']],
    'restore via HA-shaped' => ['post', 'doctors.restore', ['view doctors', 'create doctors', 'edit doctors', 'delete doctors']],
]);

it('forbids a wrong-verb permission on each doctors route', function (string $method, string $route, array $permissions) {
    $this->actingAs(docScopeUser($permissions));

    if ($route === 'doctors.restore' || $route === 'doctors.trashed') {
        docScopeTrash($this->doctor);
    }

    $doctor = docScopeNeedsDoctor($route) ? $this->doctor : null;

    docScopeRequest($this, $method, $route, $doctor)->assertForbidden();
})->with('doctors escalations');

// ── 2. Positive control: exactly the new permission passes ───────────────

dataset('doctors positive controls', [
    'index' => ['get', 'doctors.index', 'view doctors'],
    'data' => ['get', 'doctors.data', 'view doctors'],
    'show' => ['get', 'doctors.show', 'view doctors'],
    'create' => ['get', 'doctors.create', 'create doctors'],
    'store' => ['post', 'doctors.store', 'create doctors'],
    'edit' => ['get', 'doctors.edit', 'edit doctors'],
    'update' => ['put', 'doctors.update', 'edit doctors'],
    'email-available via create' => ['get', 'doctors.email-available', 'create doctors'],
    'email-available via edit' => ['get', 'doctors.email-available', 'edit doctors'],
    'destroy' => ['delete', 'doctors.destroy', 'delete doctors'],
    'trashed' => ['get', 'doctors.trashed', 'restore doctors'],
    'restore' => ['post', 'doctors.restore', 'restore doctors'],
]);

it('allows exactly the new permission on each doctors route', function (string $method, string $route, string $permission) {
    $this->actingAs(docScopeUser([$permission]));

    if ($route === 'doctors.restore' || $route === 'doctors.trashed') {
        docScopeTrash($this->doctor);
    }

    $doctor = docScopeNeedsDoctor($route) ? $this->doctor : null;

    $response = docScopeRequest($this, $method, $route, $doctor);

    expect($response->getStatusCode())->not->toBe(403)->not->toBe(404);

    if ($method === 'get') {
        $response->assertOk();

        return;
    }

    $response->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('error')
        ->assertSessionHas('success');
})->with('doctors positive controls');

// ── 3. Side effects: a blocked write writes nothing ──────────────────────

it('creates no doctor or user when view doctors posts store', function () {
    $this->actingAs(docScopeUser(['view doctors']));
    $doctors = Doctor::withTrashed()->count();
    $users = User::count();

    $response = docScopeRequest($this, 'post', 'doctors.store');

    expect(Doctor::withTrashed()->count())->toBe($doctors)
        ->and(User::count())->toBe($users)
        ->and(Doctor::withTrashed()->where('name', 'Doc Scope Name')->exists())->toBeFalse();
    $response->assertForbidden();
});

it('creates no doctor or user when edit doctors posts store', function () {
    $this->actingAs(docScopeUser(['edit doctors']));
    $doctors = Doctor::withTrashed()->count();
    $users = User::count();

    $response = docScopeRequest($this, 'post', 'doctors.store');

    expect(Doctor::withTrashed()->count())->toBe($doctors)
        ->and(User::count())->toBe($users);
    $response->assertForbidden();
});

it('leaves the doctor and user unchanged when view doctors puts update', function () {
    $this->actingAs(docScopeUser(['view doctors']));
    $userName = $this->doctor->user->name;

    $response = docScopeRequest($this, 'put', 'doctors.update', $this->doctor);

    expect($this->doctor->fresh()->name)->toBe('Doc Scope Fixture')
        ->and($this->doctor->user->fresh()->name)->toBe($userName)
        ->and($this->doctor->user->fresh()->email)->toBe($this->doctor->email);
    $response->assertForbidden();
});

it('leaves the doctor unchanged when create doctors puts update', function () {
    $this->actingAs(docScopeUser(['create doctors']));

    $response = docScopeRequest($this, 'put', 'doctors.update', $this->doctor);

    expect($this->doctor->fresh()->name)->toBe('Doc Scope Fixture');
    $response->assertForbidden();
});

it('does not soft-delete or unregister when view doctors deletes destroy', function () {
    $this->actingAs(docScopeUser(['view doctors']));

    $response = docScopeRequest($this, 'delete', 'doctors.destroy', $this->doctor);

    expect(Doctor::withTrashed()->find($this->doctor->id)->deleted_at)->toBeNull()
        ->and($this->doctor->fresh()->status)->toBe('active')
        ->and(User::find($this->doctor->user_id))->not->toBeNull()
        ->and(TenantUser::where('email', $this->doctor->email)->exists())->toBeTrue();
    $response->assertForbidden();
});

it('does not soft-delete or unregister when edit doctors deletes destroy', function () {
    $this->actingAs(docScopeUser(['edit doctors']));

    $response = docScopeRequest($this, 'delete', 'doctors.destroy', $this->doctor);

    expect(Doctor::withTrashed()->find($this->doctor->id)->deleted_at)->toBeNull()
        ->and(User::find($this->doctor->user_id))->not->toBeNull()
        ->and(TenantUser::where('email', $this->doctor->email)->exists())->toBeTrue();
    $response->assertForbidden();
});

it('does not restore a trashed doctor when delete doctors posts restore', function () {
    $this->actingAs(docScopeUser(['view doctors', 'delete doctors']));
    docScopeTrash($this->doctor);

    $response = docScopeRequest($this, 'post', 'doctors.restore', $this->doctor);

    $row = Doctor::withTrashed()->find($this->doctor->id);
    expect($row->deleted_at)->not->toBeNull()
        ->and($row->status)->toBe('inactive')
        ->and(TenantUser::where('email', $this->doctor->email)->exists())->toBeFalse();
    $response->assertForbidden();
});

// ── 4. Soft delete ───────────────────────────────────────────────────────

it('soft-deletes via destroy: keeps the user, drops the login mapping, hides from data', function () {
    $this->actingAs(docScopeUser(['view doctors', 'delete doctors', 'restore doctors']));
    $id = $this->doctor->id;
    $userId = $this->doctor->user_id;
    $email = $this->doctor->email;

    expect(TenantUser::where('email', $email)->exists())->toBeTrue();

    docScopeRequest($this, 'delete', 'doctors.destroy', $this->doctor)
        ->assertRedirect()
        ->assertSessionHas('success');

    $row = Doctor::withTrashed()->find($id);
    expect($row)->not->toBeNull()
        ->and($row->deleted_at)->not->toBeNull()
        ->and($row->status)->toBe('inactive')
        ->and(Doctor::find($id))->toBeNull()
        ->and(User::find($userId))->not->toBeNull()
        ->and(TenantUser::where('email', $email)->where('tenant_id', 1)->exists())->toBeFalse();

    $data = docScopeRequest($this, 'get', 'doctors.data');
    $data->assertOk();
    expect($data->getContent())->not->toContain('Doc Scope Fixture');

    docScopeRequest($this, 'get', 'doctors.trashed')
        ->assertOk()
        ->assertSee('Doc Scope Fixture');
});

it('shows a live doctor in data but not on trashed', function () {
    $this->actingAs(docScopeUser(['view doctors', 'restore doctors']));

    $data = docScopeRequest($this, 'get', 'doctors.data');
    $data->assertOk();
    expect($data->getContent())->toContain('Doc Scope Fixture');

    docScopeRequest($this, 'get', 'doctors.trashed')
        ->assertOk()
        ->assertDontSee('Doc Scope Fixture');
});

it('404s show, edit and update for a soft-deleted doctor', function () {
    $this->actingAs(docScopeUser(['view doctors', 'edit doctors']));
    docScopeTrash($this->doctor);

    docScopeRequest($this, 'get', 'doctors.show', $this->doctor)->assertNotFound();
    docScopeRequest($this, 'get', 'doctors.edit', $this->doctor)->assertNotFound();
    docScopeRequest($this, 'put', 'doctors.update', $this->doctor)->assertNotFound();
});

it('keeps a soft-deleted doctor email taken because the user row remains', function () {
    $this->actingAs(docScopeUser(['create doctors']));
    docScopeTrash($this->doctor);

    // The user row (and its unique email) remains, so the email is still taken.
    $this->get(route('doctors.email-available', ['email' => $this->doctor->email]))
        ->assertOk()
        ->assertJson(['available' => false]);
});

it('never force-deletes the doctor or the user on destroy', function () {
    $this->actingAs(docScopeUser(['delete doctors']));
    $id = $this->doctor->id;
    $userId = $this->doctor->user_id;

    docScopeRequest($this, 'delete', 'doctors.destroy', $this->doctor)->assertRedirect();

    expect(Doctor::withTrashed()->find($id))->not->toBeNull()
        ->and(User::find($userId))->not->toBeNull();
});

it('does not delete a doctor without a linked user and still soft-deletes', function () {
    $this->actingAs(docScopeUser(['delete doctors']));
    $orphan = docScopeDoctor();
    Doctor::withTrashed()->whereKey($orphan->id)->update(['user_id' => null]);

    docScopeRequest($this, 'delete', 'doctors.destroy', $orphan->fresh())
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Doctor::withTrashed()->find($orphan->id)->deleted_at)->not->toBeNull();
});

// ── 5. HA-shaped user: destroy ok, trashed + restore forbidden ───────────

it('lets an HA-shaped user destroy but forbids trashed and restore', function () {
    $this->actingAs(docScopeUser(['view doctors', 'create doctors', 'edit doctors', 'delete doctors']));
    $id = $this->doctor->id;

    docScopeRequest($this, 'delete', 'doctors.destroy', $this->doctor)
        ->assertRedirect()
        ->assertSessionHas('success');

    docScopeRequest($this, 'get', 'doctors.trashed')->assertForbidden();
    docScopeRequest($this, 'post', 'doctors.restore', $this->doctor)->assertForbidden();

    expect(Doctor::withTrashed()->find($id)->deleted_at)->not->toBeNull()
        ->and(Doctor::find($id))->toBeNull();
});

// ── 6. Restore holder (SA-shaped) ────────────────────────────────────────

it('restores a trashed doctor: active again, login mapping back, in index data', function () {
    $this->actingAs(docScopeUser(['view doctors', 'delete doctors', 'restore doctors']));
    $id = $this->doctor->id;
    $email = $this->doctor->email;
    docScopeTrash($this->doctor);

    expect(TenantUser::where('email', $email)->exists())->toBeFalse();

    docScopeRequest($this, 'post', 'doctors.restore', $this->doctor)
        ->assertRedirect()
        ->assertSessionHas('success');

    $row = Doctor::withTrashed()->find($id);
    expect(Doctor::find($id))->not->toBeNull()
        ->and($row->deleted_at)->toBeNull()
        ->and($row->status)->toBe('active')
        ->and(TenantUser::where('email', $email)->where('tenant_id', 1)->exists())->toBeTrue();

    $data = docScopeRequest($this, 'get', 'doctors.data');
    $data->assertOk();
    expect($data->getContent())->toContain('Doc Scope Fixture');

    $this->get(route('doctors.index'))->assertOk();
});

it('404s restore for a live (not trashed) doctor', function () {
    $this->actingAs(docScopeUser(['restore doctors']));

    docScopeRequest($this, 'post', 'doctors.restore', $this->doctor)->assertNotFound();
});

// ── 7. Assignments: Spatie permission, not a role name ───────────────────

it('registers doctor.assignments behind the view doctor assignments permission', function () {
    $route = Route::getRoutes()->getByName('doctor.assignments');

    expect($route)->not->toBeNull()
        ->and($route->gatherMiddleware())->toContain('permission:view doctor assignments');
});

it('allows a view doctor assignments holder with a doctor profile (no Doctor role)', function () {
    $user = docScopeUser(['view doctor assignments']);
    Doctor::withTrashed()->whereKey($this->doctor->id)->update(['user_id' => $user->id]);

    expect($user->hasRole('Doctor'))->toBeFalse();

    $this->actingAs($user);

    docScopeRequest($this, 'get', 'doctor.assignments')->assertOk();
});

it('forbids a user without view doctor assignments even with the Doctor role', function () {
    $user = docScopeUser([]);
    $user->assignRole(\App\Models\Role::firstOrCreate(['name' => 'Doctor']));
    Doctor::withTrashed()->whereKey($this->doctor->id)->update(['user_id' => $user->id]);

    $this->actingAs($user);

    docScopeRequest($this, 'get', 'doctor.assignments')->assertForbidden();
});

it('forbids a user with no permissions on assignments', function () {
    $this->actingAs(docScopeUser([]));

    docScopeRequest($this, 'get', 'doctor.assignments')->assertForbidden();
});

it('404s assignments for a holder without a doctor profile', function () {
    $this->actingAs(docScopeUser(['view doctor assignments']));

    docScopeRequest($this, 'get', 'doctor.assignments')->assertNotFound();
});

it('404s assignments when the holder doctor profile is soft-deleted', function () {
    $user = docScopeUser(['view doctor assignments']);
    Doctor::withTrashed()->whereKey($this->doctor->id)->update(['user_id' => $user->id]);
    docScopeTrash($this->doctor);

    $this->actingAs($user);

    docScopeRequest($this, 'get', 'doctor.assignments')->assertNotFound();
});

it('does not use hasRole in the doctors controller', function () {
    $source = file_get_contents(app_path('Http/Controllers/DoctorController.php'));

    expect($source)->not->toContain('hasRole');
});

// ── 8. AD-1 redirects ────────────────────────────────────────────────────

it('redirects a write-only create holder after store to create with the success flash', function () {
    $this->actingAs(docScopeUser(['create doctors']));

    docScopeRequest($this, 'post', 'doctors.store')
        ->assertRedirect(route('doctors.create'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    expect(Doctor::where('name', 'Doc Scope Name')->exists())->toBeTrue();
});

it('redirects create + view after store to the index with the success flash', function () {
    $this->actingAs(docScopeUser(['create doctors', 'view doctors']));

    docScopeRequest($this, 'post', 'doctors.store')
        ->assertRedirect(route('doctors.index'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');
});

it('redirects an edit-only holder after update to edit with the success flash', function () {
    $this->actingAs(docScopeUser(['edit doctors']));

    docScopeRequest($this, 'put', 'doctors.update', $this->doctor)
        ->assertRedirect(route('doctors.edit', $this->doctor))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');
});

it('redirects edit + view after update to the index with the success flash', function () {
    $this->actingAs(docScopeUser(['edit doctors', 'view doctors']));

    docScopeRequest($this, 'put', 'doctors.update', $this->doctor)
        ->assertRedirect(route('doctors.index'))
        ->assertSessionHas('success');
});

it('redirects a delete-only holder after destroy back with the success flash', function () {
    $this->actingAs(docScopeUser(['delete doctors']));
    $previous = url('/dashboard?from=doc-scope-destroy');
    $this->from($previous);

    docScopeRequest($this, 'delete', 'doctors.destroy', $this->doctor)
        ->assertRedirect($previous)
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');
});

it('redirects delete + view after destroy to the index with the success flash', function () {
    $this->actingAs(docScopeUser(['delete doctors', 'view doctors']));
    $this->from(url('/dashboard?from=doc-scope-destroy'));

    docScopeRequest($this, 'delete', 'doctors.destroy', $this->doctor)
        ->assertRedirect(route('doctors.index'))
        ->assertSessionHas('success');
});

it('redirects a restore-only holder after restore to trashed with the success flash', function () {
    $this->actingAs(docScopeUser(['restore doctors']));
    docScopeTrash($this->doctor);

    docScopeRequest($this, 'post', 'doctors.restore', $this->doctor)
        ->assertRedirect(url('doctors/trashed'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');
});

it('redirects restore + view after restore to the index with the success flash', function () {
    $this->actingAs(docScopeUser(['restore doctors', 'view doctors']));
    docScopeTrash($this->doctor);

    docScopeRequest($this, 'post', 'doctors.restore', $this->doctor)
        ->assertRedirect(route('doctors.index'))
        ->assertSessionHas('success');
});

// ── 9. UI gating (Blade / JS flags — Task 7) ─────────────────────────────

it('hides Add New, edit/delete flags and trash link from a view-only user on the index', function () {
    $this->actingAs(docScopeUser(['view doctors']));

    $this->get(route('doctors.index'))
        ->assertOk()
        ->assertDontSee('Add New Doctor')
        ->assertDontSee(e(route('doctors.create')), false)
        ->assertDontSee('data-can-edit', false)
        ->assertDontSee('data-can-delete', false)
        ->assertDontSee('doctors/trashed', false);
});

it('shows Add New Doctor to create doctors holders', function () {
    $this->actingAs(docScopeUser(['view doctors', 'create doctors']));

    $this->get(route('doctors.index'))
        ->assertOk()
        ->assertSee('Add New Doctor')
        ->assertSee(e(route('doctors.create')), false)
        ->assertDontSee('data-can-edit', false)
        ->assertDontSee('data-can-delete', false)
        ->assertDontSee('doctors/trashed', false);
});

it('shows data-can-edit to edit doctors holders only', function () {
    $this->actingAs(docScopeUser(['view doctors', 'edit doctors']));

    $this->get(route('doctors.index'))
        ->assertOk()
        ->assertSee('data-can-edit="1"', false)
        ->assertDontSee('data-can-delete', false)
        ->assertDontSee('Add New Doctor');
});

it('shows data-can-delete to delete doctors holders without the trash link', function () {
    $this->actingAs(docScopeUser(['view doctors', 'delete doctors']));

    $this->get(route('doctors.index'))
        ->assertOk()
        ->assertSee('data-can-delete="1"', false)
        ->assertDontSee('doctors/trashed', false)
        ->assertDontSee('data-can-edit', false)
        ->assertDontSee('Add New Doctor');
});

it('shows the trash link to restore doctors holders', function () {
    $this->actingAs(docScopeUser(['view doctors', 'restore doctors']));

    $this->get(route('doctors.index'))
        ->assertOk()
        ->assertSee(e(url('doctors/trashed')), false)
        ->assertDontSee('data-can-delete', false)
        ->assertDontSee('data-can-edit', false);
});

it('hides Edit and delete from a view-only user on show', function () {
    $this->actingAs(docScopeUser(['view doctors']));

    $this->get(route('doctors.show', $this->doctor))
        ->assertOk()
        ->assertDontSee(e(route('doctors.edit', $this->doctor)), false)
        ->assertDontSee(e(route('doctors.destroy', $this->doctor)), false);
});

it('shows Edit on show for edit doctors holders', function () {
    $this->actingAs(docScopeUser(['view doctors', 'edit doctors']));

    $this->get(route('doctors.show', $this->doctor))
        ->assertOk()
        ->assertSee(e(route('doctors.edit', $this->doctor)), false);
});

it('shows soft-delete on show for delete doctors holders', function () {
    $this->actingAs(docScopeUser(['view doctors', 'delete doctors']));

    $this->get(route('doctors.show', $this->doctor))
        ->assertOk()
        ->assertSee('action="'.e(route('doctors.destroy', $this->doctor)).'"', false);
});

it('shows Restore on the trashed page to restore doctors holders', function () {
    $this->actingAs(docScopeUser(['restore doctors']));
    docScopeTrash($this->doctor);

    $this->get(url('doctors/trashed'))
        ->assertOk()
        ->assertSee('Doc Scope Fixture')
        ->assertSee('Restore')
        ->assertSee('action="'.e(url('doctors/'.$this->doctor->id.'/restore')).'"', false);
});
