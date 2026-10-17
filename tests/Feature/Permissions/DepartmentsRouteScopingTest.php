<?php

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\Department;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;

/*
|--------------------------------------------------------------------------
| Departments route scoping (RBAC wave 2, Task 1)
|--------------------------------------------------------------------------
| Each of the 7 department resource routes accepts exactly one action permission.
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

    depScopeTenant();

    $this->department = Department::create([
        'name' => 'Dep Scope Fixture',
        'code' => 'DEP-SCOPE-'.uniqid(),
        'description' => 'Fixture department',
        'status' => 'active',
    ]);
});

function depScopeTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => $module === 'departments');

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function depScopeUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Departments Scope User',
        'email' => 'dep-scope-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

function depScopePayload(string $route): array
{
    return match ($route) {
        'departments.store' => [
            'name' => 'Dep Scope New',
            'description' => 'Created via scoping test',
            'status' => 'active',
        ],
        'departments.update' => [
            'name' => 'Dep Scope Renamed',
            'description' => 'Updated via scoping test',
            'status' => 'active',
        ],
        default => [],
    };
}

function depScopeRequest($test, string $method, string $route, ?Department $department = null)
{
    $params = $department ? ['department' => $department->id] : [];
    $url = route($route, $params);

    return $method === 'get'
        ? $test->get($url)
        : $test->{$method}($url, depScopePayload($route));
}

// ── (a) Deny: a wrong-verb holder that passes today must get 403 ──────────

dataset('departments escalations', [
    'create via view only' => ['get', 'departments.create', ['view departments']],
    'store via view only' => ['post', 'departments.store', ['view departments']],
    'edit via view only' => ['get', 'departments.edit', ['view departments']],
    'update via view only' => ['put', 'departments.update', ['view departments']],
    'destroy via view only' => ['delete', 'departments.destroy', ['view departments']],
    'index via create only' => ['get', 'departments.index', ['create departments']],
    'show via create only' => ['get', 'departments.show', ['create departments']],
    'destroy via edit only' => ['delete', 'departments.destroy', ['edit departments']],
]);

it('forbids a wrong-verb permission on each department route', function (string $method, string $route, array $permissions) {
    $this->actingAs(depScopeUser($permissions));

    $needsDepartment = in_array($route, ['departments.show', 'departments.edit', 'departments.update', 'departments.destroy'], true);

    depScopeRequest($this, $method, $route, $needsDepartment ? $this->department : null)->assertForbidden();
})->with('departments escalations');

// ── (b) Positive control: exactly the new permission passes ───────────────

dataset('departments positive controls', [
    'index' => ['get', 'departments.index', 'view departments'],
    'create' => ['get', 'departments.create', 'create departments'],
    'store' => ['post', 'departments.store', 'create departments'],
    'show' => ['get', 'departments.show', 'view departments'],
    'edit' => ['get', 'departments.edit', 'edit departments'],
    'update' => ['put', 'departments.update', 'edit departments'],
    'destroy' => ['delete', 'departments.destroy', 'delete departments'],
]);

it('allows exactly the new permission on each department route', function (string $method, string $route, string $permission) {
    $this->actingAs(depScopeUser([$permission]));

    $needsDepartment = in_array($route, ['departments.show', 'departments.edit', 'departments.update', 'departments.destroy'], true);

    $response = depScopeRequest($this, $method, $route, $needsDepartment ? $this->department : null);

    if ($method === 'get') {
        $response->assertOk();

        return;
    }

    $response->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('error')
        ->assertSessionHas('success');
    expect($response->getStatusCode())->not->toBe(403);
})->with('departments positive controls');

// ── (c) Side effects: a blocked write writes nothing ──────────────────────

it('creates no department when view departments posts store', function () {
    $this->actingAs(depScopeUser(['view departments']));
    $before = Department::count();

    $response = depScopeRequest($this, 'post', 'departments.store');

    expect(Department::count())->toBe($before)
        ->and(Department::where('name', 'Dep Scope New')->exists())->toBeFalse();
    $response->assertForbidden();
});

it('leaves the department name unchanged when view departments puts update', function () {
    $this->actingAs(depScopeUser(['view departments']));

    $response = depScopeRequest($this, 'put', 'departments.update', $this->department);

    expect($this->department->fresh()->name)->toBe('Dep Scope Fixture');
    $response->assertForbidden();
});

it('does not delete the department when view departments deletes destroy', function () {
    $this->actingAs(depScopeUser(['view departments']));
    $id = $this->department->id;

    $response = depScopeRequest($this, 'delete', 'departments.destroy', $this->department);

    expect(Department::find($id))->not->toBeNull();
    $response->assertForbidden();
});

// ── AC-1: seeded Hospital Administrator loses destroy only ────────────────

it('denies the seeded Hospital Administrator destroy but keeps index create and edit', function () {
    $this->seed(RolePermissionSeeder::class);

    $ha = User::create([
        'name' => 'Seeded HA Departments',
        'email' => 'dep-scope-ha-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $ha->assignRole('Hospital Administrator');
    $this->actingAs($ha);

    depScopeRequest($this, 'delete', 'departments.destroy', $this->department)->assertForbidden();
    expect(Department::find($this->department->id))->not->toBeNull();

    depScopeRequest($this, 'get', 'departments.index')->assertOk();
    depScopeRequest($this, 'get', 'departments.create')->assertOk();
    depScopeRequest($this, 'get', 'departments.edit', $this->department)->assertOk();
});
