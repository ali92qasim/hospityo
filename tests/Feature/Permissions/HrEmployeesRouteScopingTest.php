<?php

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;

/*
|--------------------------------------------------------------------------
| HR employees + designations route scoping (RBAC wave 2, Task 4)
|--------------------------------------------------------------------------
| Each of the 16 routes (9 employees, 7 designations) accepts exactly its
| action permission; reads additionally accept the legacy `view hr`.
*/

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
    $this->withoutVite();
    Storage::fake('public');

    $this->artisan('migrate', [
        '--path' => 'database/migrations/tenant',
        '--database' => 'tenant',
    ]);

    hrScopeTenant();

    $this->designation = Designation::create([
        'name' => 'Hr Scope Designation',
        'category' => 'admin',
        'description' => 'Fixture designation',
    ]);

    $this->employee = Employee::create([
        'name' => 'Hr Scope Employee',
        'joining_date' => '2026-01-01',
        'employment_type' => 'full_time',
        'status' => 'active',
    ]);

    $this->document = EmployeeDocument::create([
        'employee_id' => $this->employee->id,
        'title' => 'Hr Scope Document',
        'document_type' => 'other',
        'file_path' => 'employee-docs/hr-scope-fixture.pdf',
    ]);
});

function hrScopeTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, ['hr', 'hr.employees'], true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function hrScopeUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Hr Scope User',
        'email' => 'hr-scope-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

function hrScopePayload(string $route): array
{
    return match ($route) {
        'hr.employees.store' => [
            'name' => 'Hr Scope New Employee',
            'joining_date' => '2026-02-01',
        ],
        'hr.employees.update' => [
            'name' => 'Hr Scope Renamed Employee',
            'employment_type' => 'full_time',
            'joining_date' => '2026-01-01',
            'status' => 'active',
        ],
        'hr.employees.upload-document' => [
            'title' => 'Hr Scope Uploaded',
            'document_type' => 'other',
            'file' => UploadedFile::fake()->create('hr-scope.pdf', 10, 'application/pdf'),
        ],
        'hr.designations.store' => [
            'name' => 'Hr Scope New Designation',
            'category' => 'admin',
            'description' => 'Created via scoping test',
        ],
        'hr.designations.update' => [
            'name' => 'Hr Scope Renamed Designation',
            'category' => 'admin',
            'description' => 'Updated via scoping test',
        ],
        default => [],
    };
}

/** Route parameters for a given route name, from the fixtures. */
function hrScopeParams($test, string $route): array
{
    return match ($route) {
        'hr.employees.show', 'hr.employees.edit', 'hr.employees.update',
        'hr.employees.destroy', 'hr.employees.upload-document' => ['employee' => $test->employee->id],
        'hr.employees.delete-document' => ['document' => $test->document->id],
        'hr.designations.show', 'hr.designations.edit', 'hr.designations.update',
        'hr.designations.destroy' => ['designation' => $test->designation->id],
        default => [],
    };
}

function hrScopeRequest($test, string $method, string $route)
{
    $url = route($route, hrScopeParams($test, $route));

    return $method === 'get'
        ? $test->get($url)
        : $test->{$method}($url, hrScopePayload($route));
}

// ── (a) Deny: a wrong-verb holder that passes today must get 403 ──────────

dataset('hr employees + designations escalations', [
    // employees
    'employees.create via view employees' => ['get', 'hr.employees.create', ['view employees']],
    'employees.store via view employees' => ['post', 'hr.employees.store', ['view employees']],
    'employees.store via view hr' => ['post', 'hr.employees.store', ['view hr']],
    'employees.edit via view employees' => ['get', 'hr.employees.edit', ['view employees']],
    'employees.update via view employees' => ['put', 'hr.employees.update', ['view employees']],
    'employees.update via view hr' => ['put', 'hr.employees.update', ['view hr']],
    'employees.destroy via view employees' => ['delete', 'hr.employees.destroy', ['view employees']],
    'employees.destroy via view hr' => ['delete', 'hr.employees.destroy', ['view hr']],
    'employees.destroy via edit employees' => ['delete', 'hr.employees.destroy', ['edit employees']],
    'employees.index via create employees' => ['get', 'hr.employees.index', ['create employees']],
    'employees.show via create employees' => ['get', 'hr.employees.show', ['create employees']],
    'employees.upload-document via view hr' => ['post', 'hr.employees.upload-document', ['view hr']],
    'employees.upload-document via view employee documents' => ['post', 'hr.employees.upload-document', ['view employee documents']],
    'employees.delete-document via view hr' => ['delete', 'hr.employees.delete-document', ['view hr']],
    'employees.delete-document via create employee documents' => ['delete', 'hr.employees.delete-document', ['create employee documents']],
    // designations
    'designations.create via view designations' => ['get', 'hr.designations.create', ['view designations']],
    'designations.store via view designations' => ['post', 'hr.designations.store', ['view designations']],
    'designations.store via view hr' => ['post', 'hr.designations.store', ['view hr']],
    'designations.edit via view designations' => ['get', 'hr.designations.edit', ['view designations']],
    'designations.update via view designations' => ['put', 'hr.designations.update', ['view designations']],
    'designations.update via view hr' => ['put', 'hr.designations.update', ['view hr']],
    'designations.destroy via view designations' => ['delete', 'hr.designations.destroy', ['view designations']],
    'designations.destroy via view hr' => ['delete', 'hr.designations.destroy', ['view hr']],
    'designations.destroy via edit designations' => ['delete', 'hr.designations.destroy', ['edit designations']],
    'designations.index via create designations' => ['get', 'hr.designations.index', ['create designations']],
    'designations.show via create designations' => ['get', 'hr.designations.show', ['create designations']],
]);

it('forbids a wrong-verb permission on each HR employee/designation route', function (string $method, string $route, array $permissions) {
    $this->actingAs(hrScopeUser($permissions));

    hrScopeRequest($this, $method, $route)->assertForbidden();
})->with('hr employees + designations escalations');

// ── (b) Positive control: exactly the new permission passes ───────────────

dataset('hr employees + designations positive controls', [
    'employees.index via view employees' => ['get', 'hr.employees.index', 'view employees'],
    'employees.index via view hr' => ['get', 'hr.employees.index', 'view hr'],
    'employees.create' => ['get', 'hr.employees.create', 'create employees'],
    'employees.store' => ['post', 'hr.employees.store', 'create employees'],
    'employees.show via view employees' => ['get', 'hr.employees.show', 'view employees'],
    'employees.show via view hr' => ['get', 'hr.employees.show', 'view hr'],
    'employees.edit' => ['get', 'hr.employees.edit', 'edit employees'],
    'employees.update' => ['put', 'hr.employees.update', 'edit employees'],
    'employees.destroy' => ['delete', 'hr.employees.destroy', 'delete employees'],
    'employees.upload-document' => ['post', 'hr.employees.upload-document', 'create employee documents'],
    'employees.delete-document' => ['delete', 'hr.employees.delete-document', 'delete employee documents'],
    'designations.index via view designations' => ['get', 'hr.designations.index', 'view designations'],
    'designations.index via view hr' => ['get', 'hr.designations.index', 'view hr'],
    'designations.create' => ['get', 'hr.designations.create', 'create designations'],
    'designations.store' => ['post', 'hr.designations.store', 'create designations'],
    'designations.show via view designations' => ['get', 'hr.designations.show', 'view designations'],
    'designations.show via view hr' => ['get', 'hr.designations.show', 'view hr'],
    'designations.edit' => ['get', 'hr.designations.edit', 'edit designations'],
    'designations.update' => ['put', 'hr.designations.update', 'edit designations'],
    'designations.destroy' => ['delete', 'hr.designations.destroy', 'delete designations'],
]);

it('allows exactly the new permission on each HR employee/designation route', function (string $method, string $route, string $permission) {
    $this->actingAs(hrScopeUser([$permission]));

    $response = hrScopeRequest($this, $method, $route);

    // hr.designations.show has no controller method (pre-existing): the
    // request clears middleware and then fails inside the router, so only
    // "not forbidden" is assertable for it.
    if ($route === 'hr.designations.show') {
        expect($response->getStatusCode())->not->toBe(403);

        return;
    }

    if ($method === 'get') {
        $response->assertOk();

        return;
    }

    $response->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('error')
        ->assertSessionHas('success');
    expect($response->getStatusCode())->not->toBe(403);
})->with('hr employees + designations positive controls');

// ── (c) Side effects: a blocked write writes nothing ──────────────────────

it('creates no employee when view employees posts store', function () {
    $this->actingAs(hrScopeUser(['view employees']));
    $before = Employee::count();

    $response = hrScopeRequest($this, 'post', 'hr.employees.store');

    expect(Employee::count())->toBe($before)
        ->and(Employee::where('name', 'Hr Scope New Employee')->exists())->toBeFalse();
    $response->assertForbidden();
});

it('leaves the employee unchanged when view employees puts update', function () {
    $this->actingAs(hrScopeUser(['view employees']));

    $response = hrScopeRequest($this, 'put', 'hr.employees.update');

    expect($this->employee->fresh()->name)->toBe('Hr Scope Employee');
    $response->assertForbidden();
});

it('does not delete the employee when edit employees deletes destroy', function () {
    $this->actingAs(hrScopeUser(['edit employees', 'view employees']));

    $response = hrScopeRequest($this, 'delete', 'hr.employees.destroy');

    expect(Employee::find($this->employee->id))->not->toBeNull();
    $response->assertForbidden();
});

it('stores no document when view hr posts upload-document', function () {
    $this->actingAs(hrScopeUser(['view hr']));
    $before = EmployeeDocument::count();

    $response = hrScopeRequest($this, 'post', 'hr.employees.upload-document');

    expect(EmployeeDocument::count())->toBe($before)
        ->and(Storage::disk('public')->allFiles())->toBeEmpty();
    $response->assertForbidden();
});

it('does not delete the document when view hr deletes delete-document', function () {
    $this->actingAs(hrScopeUser(['view hr']));

    $response = hrScopeRequest($this, 'delete', 'hr.employees.delete-document');

    expect(EmployeeDocument::find($this->document->id))->not->toBeNull();
    $response->assertForbidden();
});

it('creates no designation when view designations posts store', function () {
    $this->actingAs(hrScopeUser(['view designations']));
    $before = Designation::count();

    $response = hrScopeRequest($this, 'post', 'hr.designations.store');

    expect(Designation::count())->toBe($before)
        ->and(Designation::where('name', 'Hr Scope New Designation')->exists())->toBeFalse();
    $response->assertForbidden();
});

it('leaves the designation unchanged when view designations puts update', function () {
    $this->actingAs(hrScopeUser(['view designations']));

    $response = hrScopeRequest($this, 'put', 'hr.designations.update');

    expect($this->designation->fresh()->name)->toBe('Hr Scope Designation');
    $response->assertForbidden();
});

it('does not delete the designation when view hr deletes destroy', function () {
    $this->actingAs(hrScopeUser(['view hr']));

    $response = hrScopeRequest($this, 'delete', 'hr.designations.destroy');

    expect(Designation::find($this->designation->id))->not->toBeNull();
    $response->assertForbidden();
});

// ── H5/H6/H7: seeded Hospital Administrator loses the three destructive routes ──

it('denies the seeded Hospital Administrator the destructive HR routes but keeps index create and edit', function () {
    $this->seed(RolePermissionSeeder::class);

    $ha = User::create([
        'name' => 'Seeded HA Hr',
        'email' => 'hr-scope-ha-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $ha->assignRole('Hospital Administrator');
    $this->actingAs($ha);

    // H5: employees.destroy
    hrScopeRequest($this, 'delete', 'hr.employees.destroy')->assertForbidden();
    expect(Employee::find($this->employee->id))->not->toBeNull();

    // H6: designations.destroy
    hrScopeRequest($this, 'delete', 'hr.designations.destroy')->assertForbidden();
    expect(Designation::find($this->designation->id))->not->toBeNull();

    // H7: employees.delete-document
    hrScopeRequest($this, 'delete', 'hr.employees.delete-document')->assertForbidden();
    expect(EmployeeDocument::find($this->document->id))->not->toBeNull();

    // What the seeder still grants keeps working.
    hrScopeRequest($this, 'get', 'hr.employees.index')->assertOk();
    hrScopeRequest($this, 'get', 'hr.employees.create')->assertOk();
    hrScopeRequest($this, 'get', 'hr.employees.edit')->assertOk();
    hrScopeRequest($this, 'get', 'hr.designations.index')->assertOk();
    hrScopeRequest($this, 'get', 'hr.designations.create')->assertOk();
    hrScopeRequest($this, 'get', 'hr.designations.edit')->assertOk();
    expect(hrScopeRequest($this, 'post', 'hr.employees.upload-document')->getStatusCode())->not->toBe(403);
});
