<?php

use App\Models\Admission;
use App\Models\Bed;
use App\Models\Department;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Visit;
use App\Models\Ward;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

/*
|--------------------------------------------------------------------------
| Wards / Beds route scoping (RBAC wave 2, Task 11)
|--------------------------------------------------------------------------
| Each of the 12 ward/bed resource routes accepts exactly one action permission
| (show routes removed — no controller methods). beds.update-status is Task 12
| → 13 routes in scope total.
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

    wbScopeTenant();

    $this->department = Department::create([
        'name' => 'WB Scope Med',
        'code' => 'WB-MED-'.uniqid(),
        'description' => 'Fixture department',
        'status' => 'active',
    ]);

    $this->ward = Ward::create([
        'name' => 'WB Scope Ward',
        'department_id' => $this->department->id,
        'capacity' => 10,
        'ward_type' => 'general',
        'status' => 'active',
    ]);

    $this->bed = Bed::create([
        'bed_number' => 'WB-01',
        'ward_id' => $this->ward->id,
        'bed_type' => 'general',
        'daily_rate' => 500,
        'status' => 'available',
    ]);
});

function wbScopeTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => $module === 'ipd');

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function wbScopeUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Wards Beds Scope User',
        'email' => 'wb-scope-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

function wbScopePayload(string $route): array
{
    return match ($route) {
        'wards.store' => [
            'name' => 'WB Scope New Ward',
            'department_id' => test()->department->id,
            'capacity' => 8,
            'ward_type' => 'general',
            'status' => 'active',
        ],
        'wards.update' => [
            'name' => 'WB Scope Renamed Ward',
            'department_id' => test()->department->id,
            'capacity' => 12,
            'ward_type' => 'private',
            'status' => 'active',
        ],
        'beds.store' => [
            'bed_number' => 'WB-NEW-01',
            'ward_id' => test()->ward->id,
            'bed_type' => 'general',
            'daily_rate' => 750,
            'status' => 'available',
        ],
        'beds.update' => [
            'bed_number' => 'WB-01-REN',
            'ward_id' => test()->ward->id,
            'bed_type' => 'private',
            'daily_rate' => 900,
            'status' => 'maintenance',
        ],
        default => [],
    };
}

function wbScopeRequest($test, string $method, string $route, Ward|Bed|null $model = null)
{
    $params = [];
    if ($model instanceof Ward) {
        $params['ward'] = $model->id;
    } elseif ($model instanceof Bed) {
        $params['bed'] = $model->id;
    }

    $url = route($route, $params);

    return $method === 'get'
        ? $test->get($url)
        : $test->{$method}($url, wbScopePayload($route));
}

function wbScopeNeedsModel(string $route): bool
{
    return in_array($route, [
        'wards.edit', 'wards.update', 'wards.destroy',
        'beds.edit', 'beds.update', 'beds.destroy',
    ], true);
}

function wbScopeModelFor(string $route): Ward|Bed|null
{
    if (str_starts_with($route, 'wards.')) {
        return wbScopeNeedsModel($route) ? test()->ward : null;
    }

    return wbScopeNeedsModel($route) ? test()->bed : null;
}

// ── (a) Deny: a wrong-verb holder that passes today must get 403 ──────────

dataset('wards beds escalations', [
    'wards create via view only' => ['get', 'wards.create', ['view wards']],
    'wards store via view only' => ['post', 'wards.store', ['view wards']],
    'wards edit via view only' => ['get', 'wards.edit', ['view wards']],
    'wards update via view only' => ['put', 'wards.update', ['view wards']],
    'wards destroy via view only' => ['delete', 'wards.destroy', ['view wards']],
    'wards index via create only' => ['get', 'wards.index', ['create wards']],
    'wards destroy via edit only' => ['delete', 'wards.destroy', ['edit wards']],
    'beds create via view only' => ['get', 'beds.create', ['view beds']],
    'beds store via view only' => ['post', 'beds.store', ['view beds']],
    'beds edit via view only' => ['get', 'beds.edit', ['view beds']],
    'beds update via view only' => ['put', 'beds.update', ['view beds']],
    'beds destroy via view only' => ['delete', 'beds.destroy', ['view beds']],
    'beds index via create only' => ['get', 'beds.index', ['create beds']],
    'beds destroy via edit only' => ['delete', 'beds.destroy', ['edit beds']],
]);

it('forbids a wrong-verb permission on each ward and bed route', function (string $method, string $route, array $permissions) {
    $this->actingAs(wbScopeUser($permissions));

    wbScopeRequest($this, $method, $route, wbScopeModelFor($route))->assertForbidden();
})->with('wards beds escalations');

// ── (b) Positive control: exactly the new permission passes ───────────────

dataset('wards beds positive controls', [
    'wards index' => ['get', 'wards.index', 'view wards'],
    'wards create' => ['get', 'wards.create', 'create wards'],
    'wards store' => ['post', 'wards.store', 'create wards'],
    'wards edit' => ['get', 'wards.edit', 'edit wards'],
    'wards update' => ['put', 'wards.update', 'edit wards'],
    'wards destroy' => ['delete', 'wards.destroy', 'delete wards'],
    'beds index' => ['get', 'beds.index', 'view beds'],
    'beds create' => ['get', 'beds.create', 'create beds'],
    'beds store' => ['post', 'beds.store', 'create beds'],
    'beds edit' => ['get', 'beds.edit', 'edit beds'],
    'beds update' => ['put', 'beds.update', 'edit beds'],
    'beds destroy' => ['delete', 'beds.destroy', 'delete beds'],
]);

it('does not register dead wards.show or beds.show routes', function () {
    expect(Route::has('wards.show'))->toBeFalse()
        ->and(Route::has('beds.show'))->toBeFalse();
});

it('allows exactly the new permission on each ward and bed route', function (string $method, string $route, string $permission) {
    $this->actingAs(wbScopeUser([$permission]));

    $response = wbScopeRequest($this, $method, $route, wbScopeModelFor($route));

    if ($method === 'get') {
        $response->assertOk();

        return;
    }

    $response->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('error')
        ->assertSessionHas('success');
    expect($response->getStatusCode())->not->toBe(403);
})->with('wards beds positive controls');

// ── (c) Side effects: a blocked write writes nothing ──────────────────────

it('creates no ward when view wards posts store', function () {
    $this->actingAs(wbScopeUser(['view wards']));
    $before = Ward::count();

    $response = wbScopeRequest($this, 'post', 'wards.store');

    expect(Ward::count())->toBe($before)
        ->and(Ward::where('name', 'WB Scope New Ward')->exists())->toBeFalse();
    $response->assertForbidden();
});

it('leaves the ward name unchanged when view wards puts update', function () {
    $this->actingAs(wbScopeUser(['view wards']));

    $response = wbScopeRequest($this, 'put', 'wards.update', $this->ward);

    expect($this->ward->fresh()->name)->toBe('WB Scope Ward');
    $response->assertForbidden();
});

it('does not delete the ward when view wards deletes destroy', function () {
    $this->actingAs(wbScopeUser(['view wards']));
    $id = $this->ward->id;

    $response = wbScopeRequest($this, 'delete', 'wards.destroy', $this->ward);

    expect(Ward::find($id))->not->toBeNull();
    $response->assertForbidden();
});

it('creates no bed when view beds posts store', function () {
    $this->actingAs(wbScopeUser(['view beds']));
    $before = Bed::count();

    $response = wbScopeRequest($this, 'post', 'beds.store');

    expect(Bed::count())->toBe($before)
        ->and(Bed::where('bed_number', 'WB-NEW-01')->exists())->toBeFalse();
    $response->assertForbidden();
});

it('leaves the bed number unchanged when view beds puts update', function () {
    $this->actingAs(wbScopeUser(['view beds']));

    $response = wbScopeRequest($this, 'put', 'beds.update', $this->bed);

    expect($this->bed->fresh()->bed_number)->toBe('WB-01');
    $response->assertForbidden();
});

it('does not delete the bed when view beds deletes destroy', function () {
    $this->actingAs(wbScopeUser(['view beds']));
    $id = $this->bed->id;

    $response = wbScopeRequest($this, 'delete', 'beds.destroy', $this->bed);

    expect(Bed::find($id))->not->toBeNull();
    $response->assertForbidden();
});

// ── AC-1: seeded Hospital Administrator loses destroy only ────────────────

it('denies the seeded Hospital Administrator ward and bed destroy but keeps index create and edit', function () {
    $this->seed(RolePermissionSeeder::class);

    $ha = User::create([
        'name' => 'Seeded HA Wards Beds',
        'email' => 'wb-scope-ha-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $ha->assignRole('Hospital Administrator');
    $this->actingAs($ha);

    wbScopeRequest($this, 'delete', 'wards.destroy', $this->ward)->assertForbidden();
    expect(Ward::find($this->ward->id))->not->toBeNull();

    wbScopeRequest($this, 'delete', 'beds.destroy', $this->bed)->assertForbidden();
    expect(Bed::find($this->bed->id))->not->toBeNull();

    wbScopeRequest($this, 'get', 'wards.index')->assertOk();
    wbScopeRequest($this, 'get', 'wards.create')->assertOk();
    wbScopeRequest($this, 'get', 'wards.edit', $this->ward)->assertOk();

    wbScopeRequest($this, 'get', 'beds.index')->assertOk();
    wbScopeRequest($this, 'get', 'beds.create')->assertOk();
    wbScopeRequest($this, 'get', 'beds.edit', $this->bed)->assertOk();
});

// ── Seeded Nurse: view only + no edit beds; writes forbidden ──────────────

it('allows the seeded Nurse ward and bed index but forbids writes and edit beds', function () {
    $this->seed(RolePermissionSeeder::class);

    $nurse = User::create([
        'name' => 'Seeded Nurse Wards Beds',
        'email' => 'wb-scope-nurse-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $nurse->assignRole('Nurse');
    $this->actingAs($nurse);

    expect($nurse->hasPermissionTo('edit beds'))->toBeFalse()
        ->and($nurse->hasPermissionTo('change bed status'))->toBeTrue();

    wbScopeRequest($this, 'get', 'wards.index')->assertOk();
    wbScopeRequest($this, 'get', 'beds.index')->assertOk();

    foreach ([
        ['get', 'beds.create'],
        ['post', 'beds.store'],
        ['get', 'beds.edit'],
        ['put', 'beds.update'],
        ['delete', 'beds.destroy'],
        ['get', 'wards.create'],
        ['post', 'wards.store'],
        ['get', 'wards.edit'],
        ['put', 'wards.update'],
        ['delete', 'wards.destroy'],
    ] as [$method, $route]) {
        wbScopeRequest($this, $method, $route, wbScopeModelFor($route))->assertForbidden();
    }
});

it('writes nothing when the seeded Nurse tries bed store destroy or ward bed update', function () {
    $this->seed(RolePermissionSeeder::class);

    $nurse = User::create([
        'name' => 'Seeded Nurse Side Effect',
        'email' => 'wb-scope-nurse-se-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $nurse->assignRole('Nurse');
    $this->actingAs($nurse);

    $bedCount = Bed::count();
    $wardName = $this->ward->name;
    $bedNumber = $this->bed->bed_number;
    $bedId = $this->bed->id;

    wbScopeRequest($this, 'post', 'beds.store')->assertForbidden();
    expect(Bed::count())->toBe($bedCount)
        ->and(Bed::where('bed_number', 'WB-NEW-01')->exists())->toBeFalse();

    wbScopeRequest($this, 'delete', 'beds.destroy', $this->bed)->assertForbidden();
    expect(Bed::find($bedId))->not->toBeNull();

    wbScopeRequest($this, 'put', 'wards.update', $this->ward)->assertForbidden();
    expect($this->ward->fresh()->name)->toBe($wardName);

    wbScopeRequest($this, 'put', 'beds.update', $this->bed)->assertForbidden();
    expect($this->bed->fresh()->bed_number)->toBe($bedNumber);
});

// ── Task 12: beds.update-status + AD-1 + STATUS-TRANSITIONS (default) ─────

function wbScopeStatusRequest($test, Bed $bed, string $status)
{
    return $test->from(route('beds.index'))
        ->patch(route('beds.update-status', $bed), ['status' => $status]);
}

function wbScopeSnapshot(Bed $bed): array
{
    return $bed->only(['bed_number', 'ward_id', 'bed_type', 'status', 'daily_rate']);
}

it('lets a change-bed-status holder toggle available and maintenance', function () {
    $this->actingAs(wbScopeUser(['change bed status', 'view beds']));

    wbScopeStatusRequest($this, $this->bed, 'maintenance')
        ->assertRedirect(route('beds.index'))
        ->assertSessionHas('success')
        ->assertSessionMissing('error');
    expect($this->bed->fresh()->status)->toBe('maintenance');

    wbScopeStatusRequest($this, $this->bed->fresh(), 'available')
        ->assertRedirect(route('beds.index'))
        ->assertSessionHas('success');
    expect($this->bed->fresh()->status)->toBe('available');
});

it('lets an edit-beds holder use update-status without change bed status', function () {
    $this->actingAs(wbScopeUser(['edit beds', 'view beds']));

    wbScopeStatusRequest($this, $this->bed, 'maintenance')
        ->assertRedirect(route('beds.index'))
        ->assertSessionHas('success');
    expect($this->bed->fresh()->status)->toBe('maintenance');
});

it('forbids view-beds-only from update-status and writes nothing', function () {
    $this->actingAs(wbScopeUser(['view beds']));
    $before = wbScopeSnapshot($this->bed);

    wbScopeStatusRequest($this, $this->bed, 'maintenance')->assertForbidden();

    expect($this->bed->fresh()->only(array_keys($before)))->toBe($before);
});

it('rejects setting occupied via update-status and writes nothing', function () {
    $this->actingAs(wbScopeUser(['change bed status', 'view beds']));
    $before = wbScopeSnapshot($this->bed);

    wbScopeStatusRequest($this, $this->bed, 'occupied')
        ->assertSessionHasErrors('status')
        ->assertSessionMissing('success');

    expect($this->bed->fresh()->only(array_keys($before)))->toBe($before);
});

it('rejects update-status when the bed is already occupied and writes nothing', function () {
    $this->bed->update(['status' => 'occupied']);
    $this->actingAs(wbScopeUser(['change bed status', 'view beds']));
    $before = wbScopeSnapshot($this->bed->fresh());

    wbScopeStatusRequest($this, $this->bed->fresh(), 'available')
        ->assertRedirect(route('beds.index'))
        ->assertSessionHas('error');

    expect($this->bed->fresh()->only(array_keys($before)))->toBe($before);
});

it('rejects update-status when the bed has an active admission and writes nothing', function () {
    $patient = Patient::create([
        'name' => 'WB Scope Patient',
        'gender' => 'male',
        'age' => 40,
        'phone' => '03001234567',
    ]);
    $visit = Visit::create([
        'patient_id' => $patient->id,
        'visit_type' => 'ipd',
        'visit_datetime' => now(),
        'status' => 'admitted',
        'doctor_id' => null,
    ]);
    Admission::create([
        'visit_id' => $visit->id,
        'bed_id' => $this->bed->id,
        'admission_date' => now(),
        'status' => 'active',
    ]);
    // Leave bed status available to prove admission alone blocks the action.
    expect($this->bed->fresh()->status)->toBe('available');

    $this->actingAs(wbScopeUser(['change bed status', 'view beds']));
    $before = wbScopeSnapshot($this->bed->fresh());

    wbScopeStatusRequest($this, $this->bed->fresh(), 'maintenance')
        ->assertRedirect(route('beds.index'))
        ->assertSessionHas('error');

    expect($this->bed->fresh()->only(array_keys($before)))->toBe($before);
});

it('still forbids the seeded Nurse from full bed and ward writes after status route exists', function () {
    $this->seed(RolePermissionSeeder::class);

    $nurse = User::create([
        'name' => 'Seeded Nurse Status Regression',
        'email' => 'wb-scope-nurse-status-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $nurse->assignRole('Nurse');
    $this->actingAs($nurse);

    wbScopeStatusRequest($this, $this->bed, 'maintenance')
        ->assertRedirect(route('beds.index'))
        ->assertSessionHas('success');
    expect($this->bed->fresh()->status)->toBe('maintenance');

    wbScopeRequest($this, 'get', 'beds.edit', $this->bed)->assertForbidden();
    wbScopeRequest($this, 'put', 'beds.update', $this->bed)->assertForbidden();
    wbScopeRequest($this, 'post', 'beds.store')->assertForbidden();
    wbScopeRequest($this, 'delete', 'beds.destroy', $this->bed)->assertForbidden();
    wbScopeRequest($this, 'put', 'wards.update', $this->ward)->assertForbidden();
});

dataset('wards beds write redirects', [
    'wards store' => ['post', 'wards.store', 'create wards', 'wards.create', [], 'wards.index', 'view wards'],
    'wards update' => ['put', 'wards.update', 'edit wards', 'wards.edit', ['ward'], 'wards.index', 'view wards'],
    'wards destroy' => ['delete', 'wards.destroy', 'delete wards', null, [], 'wards.index', 'view wards'],
    'beds store' => ['post', 'beds.store', 'create beds', 'beds.create', [], 'beds.index', 'view beds'],
    'beds update' => ['put', 'beds.update', 'edit beds', 'beds.edit', ['bed'], 'beds.index', 'view beds'],
    'beds destroy' => ['delete', 'beds.destroy', 'delete beds', null, [], 'beds.index', 'view beds'],
]);

it('redirects a write-only ward/bed holder to the fallback with the success flash', function (
    string $method,
    string $route,
    string $write,
    ?string $fallback,
    array $fallbackFixtures,
    string $list,
    string $view,
) {
    $this->actingAs(wbScopeUser([$write]));

    $model = wbScopeModelFor($route);

    if ($method === 'delete') {
        $previous = url('/dashboard?from=wb-scope-destroy');
        $this->from($previous);
    }

    $response = wbScopeRequest($this, $method, $route, $model);

    if ($fallback === null) {
        $response->assertRedirect($previous)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        return;
    }

    $fallbackParams = [];
    foreach ($fallbackFixtures as $key) {
        $fallbackParams[$key] = $key === 'ward' ? $this->ward->id : $this->bed->id;
    }

    $response->assertRedirect(route($fallback, $fallbackParams))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');
})->with('wards beds write redirects');

it('redirects a write + view ward/bed holder to the index with the success flash', function (
    string $method,
    string $route,
    string $write,
    ?string $fallback,
    array $fallbackFixtures,
    string $list,
    string $view,
) {
    $this->actingAs(wbScopeUser([$write, $view]));

    if ($method === 'delete') {
        $this->from(url('/dashboard?from=wb-scope-destroy'));
    }

    wbScopeRequest($this, $method, $route, wbScopeModelFor($route))
        ->assertRedirect(route($list))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');
})->with('wards beds write redirects');
