<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabResult;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Support\PermissionRegistry;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

/*
|--------------------------------------------------------------------------
| Laboratory route scoping (RBAC wave 3 remainder, Task 4)
|--------------------------------------------------------------------------
| Each laboratory HTTP route (investigations / lab-tests catalog,
| investigation-orders, lab-orders, lab-results and the /lab/* aliases)
| accepts exactly one action permission. There is no lab delete: all destroy
| routes and the delete investigations / investigation orders / lab orders /
| lab results permissions are gone. Imaging routes are out of scope (Task 5).
*/

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
    $this->withoutVite();

    labScopeTenant();

    $this->creator = labScopeUser([]);

    $patient = Patient::create([
        'name' => 'Lab Scope Patient',
        'gender' => 'male',
        'age' => 30,
        'phone' => '03001234567',
    ]);
    $department = Department::create([
        'name' => 'Lab Scope Dept '.uniqid(),
        'code' => 'LS'.uniqid(),
        'status' => 'active',
    ]);
    $doctor = Doctor::create([
        'name' => 'Dr Lab Scope',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005550001',
        'email' => 'lab-scope-doctor-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $this->labTest = LabTest::create([
        'code' => 'LS'.uniqid(),
        'name' => 'Scope CBC',
        'category' => 'hematology',
        'sample_type' => 'blood',
        'price' => 500,
        'turnaround_time' => 4,
        'is_active' => true,
    ]);
    $this->order = LabOrder::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'priority' => 'routine',
        'status' => 'ordered',
        'ordered_at' => now(),
    ]);
    $this->orderItem = LabOrderItem::create([
        'lab_order_id' => $this->order->id,
        'lab_test_id' => $this->labTest->id,
        'quantity' => 1,
        'priority' => 'routine',
        'status' => 'ordered',
    ]);
    $this->result = LabResult::create([
        'lab_order_id' => $this->order->id,
        'results' => [],
        'status' => 'preliminary',
        'technician_id' => $this->creator->id,
        'tested_at' => now(),
    ]);
});

function labScopeTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')->andReturn(true);

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function labScopeUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Lab Scope User',
        'email' => 'lab-scope-user-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

function labScopeUrl($test, string $route): string
{
    $parameters = [];
    foreach (Route::getRoutes()->getByName($route)->parameterNames() as $name) {
        $parameters[$name] = match ($name) {
            'labTest' => $test->labTest->id,
            'investigationOrder', 'labOrder' => $test->order->id,
            'orderItem' => $test->orderItem->id,
            'labResult' => $test->result->id,
        };
    }

    return route($route, $parameters);
}

function labScopeRequest($test, string $method, string $route)
{
    $url = labScopeUrl($test, $route);

    return $method === 'get' ? $test->get($url) : $test->{$method}($url, []);
}

/** @return array<string, array{0: string, 1: string, 2: string}> [method, route, permission] */
function labScopeRoutes(): array
{
    $routes = [];
    $add = function (string $method, string $route, string $permission) use (&$routes) {
        $routes["{$route} ({$permission})"] = [$method, $route, $permission];
    };

    // Catalog: investigations, lab-tests and /lab/tests
    foreach ([
        ['get', 'investigations.index', 'view investigations'],
        ['get', 'investigations.data', 'view investigations'],
        ['get', 'investigations.show', 'view investigations'],
        ['get', 'investigations.create', 'create investigations'],
        ['post', 'investigations.store', 'create investigations'],
        ['post', 'investigations.import', 'create investigations'],
        ['get', 'investigations.import-status', 'create investigations'],
        ['get', 'investigations.edit', 'edit investigations'],
        ['put', 'investigations.update', 'edit investigations'],
        ['get', 'lab-tests.index', 'view investigations'],
        ['get', 'lab-tests.show', 'view investigations'],
        ['get', 'lab-tests.create', 'create investigations'],
        ['post', 'lab-tests.store', 'create investigations'],
        ['get', 'lab-tests.edit', 'edit investigations'],
        ['put', 'lab-tests.update', 'edit investigations'],
        ['get', 'lab.tests.index', 'view investigations'],
        ['get', 'lab.tests.data', 'view investigations'],
        ['get', 'lab.tests.show', 'view investigations'],
        ['get', 'lab.tests.create', 'create investigations'],
        ['post', 'lab.tests.store', 'create investigations'],
        ['post', 'lab.tests.import', 'create investigations'],
        ['get', 'lab.tests.import-status', 'create investigations'],
        ['get', 'lab.tests.edit', 'edit investigations'],
        ['put', 'lab.tests.update', 'edit investigations'],
    ] as $r) {
        $add(...$r);
    }

    // Orders: investigation-orders, lab-orders and /lab/orders
    foreach ([
        ['get', 'investigation-orders.index', 'view investigation orders'],
        ['get', 'investigation-orders.show', 'view investigation orders'],
        ['get', 'investigation-orders.create', 'create investigation orders'],
        ['post', 'investigation-orders.store', 'create investigation orders'],
        ['get', 'investigation-orders.edit', 'edit investigation orders'],
        ['put', 'investigation-orders.update', 'edit investigation orders'],
        ['post', 'investigation-orders.collect-sample', 'edit investigation orders'],
        ['post', 'investigation-orders.receive-sample', 'edit investigation orders'],
        ['get', 'lab-orders.index', 'view lab orders'],
        ['get', 'lab-orders.show', 'view lab orders'],
        ['get', 'lab-orders.create', 'create lab orders'],
        ['post', 'lab-orders.store', 'create lab orders'],
        ['get', 'lab-orders.edit', 'edit lab orders'],
        ['put', 'lab-orders.update', 'edit lab orders'],
        ['post', 'lab-orders.collect-sample', 'edit lab orders'],
        ['post', 'lab-orders.receive-sample', 'edit lab orders'],
        ['get', 'lab.orders.index', 'view investigation orders'],
        ['get', 'lab.orders.index', 'view lab orders'],
        ['get', 'lab.orders.create', 'create investigation orders'],
        ['get', 'lab.orders.create', 'create lab orders'],
        ['post', 'lab.orders.store', 'create investigation orders'],
        ['post', 'lab.orders.store', 'create lab orders'],
    ] as $r) {
        $add(...$r);
    }

    // Results: lab-results and /lab/results
    foreach ([
        ['get', 'lab-results.index', 'view lab results'],
        ['get', 'lab-results.data', 'view lab results'],
        ['get', 'lab-results.show', 'view lab results'],
        ['get', 'lab-results.report', 'view lab results'],
        ['get', 'lab-results.share-whatsapp', 'view lab results'],
        ['get', 'investigation-orders.report', 'view lab results'],
        ['get', 'lab.results.index', 'view lab results'],
        ['get', 'lab-results.create', 'create lab results'],
        ['get', 'lab-results.create-batch', 'create lab results'],
        ['post', 'lab-results.store-batch', 'create lab results'],
        ['get', 'lab-orders.results.create', 'create lab results'],
        ['post', 'lab-orders.results.store', 'create lab results'],
        ['get', 'lab-results.edit', 'edit lab results'],
        ['put', 'lab-results.update', 'edit lab results'],
        ['post', 'lab-results.verify', 'edit lab results'],
    ] as $r) {
        $add(...$r);
    }

    return $routes;
}

dataset('lab routes', fn () => labScopeRoutes());

// ── 1. Deny: wrong-verb holders must get 403 ─────────────────────────────

it('forbids wrong-verb permissions on each laboratory route', function (string $method, string $route, string $permission) {
    $verb = strtok($permission, ' ');
    $subject = substr($permission, strlen($verb) + 1);

    $wrong = collect(['view', 'create', 'edit'])
        ->reject(fn ($v) => $v === $verb)
        ->map(fn ($v) => "{$v} {$subject}")
        ->all();

    // Also grant every verb on the *other* lab subjects: scoping is per subject.
    $this->actingAs(labScopeUser($wrong));

    labScopeRequest($this, $method, $route)->assertForbidden();
})->with('lab routes');

it('forbids a user with only delete-style lab permissions on every laboratory route', function (string $method, string $route, string $permission) {
    $this->actingAs(labScopeUser([
        'delete investigations',
        'delete investigation orders',
        'delete lab orders',
        'delete lab results',
    ]));

    labScopeRequest($this, $method, $route)->assertForbidden();
})->with('lab routes');

// ── 2. Positive control: exactly the matching permission passes ──────────

it('allows exactly the matching permission on each laboratory route', function (string $method, string $route, string $permission) {
    $this->actingAs(labScopeUser([$permission]));

    $response = labScopeRequest($this, $method, $route);

    expect($response->getStatusCode())->not->toBe(403)->not->toBe(404);
})->with('lab routes');

// ── 3. Side effects: a blocked write writes nothing ──────────────────────

it('creates no lab test when view investigations posts store', function () {
    $this->actingAs(labScopeUser(['view investigations']));
    $count = LabTest::count();

    $this->post(route('lab.tests.store'), [
        'code' => 'NOPE', 'name' => 'Nope', 'category' => 'hematology', 'sample_type' => 'blood',
        'price' => 1, 'turnaround_time' => 1,
    ])->assertForbidden();

    expect(LabTest::count())->toBe($count);
});

it('leaves the lab order untouched when view investigation orders puts update', function () {
    $this->actingAs(labScopeUser(['view investigation orders']));

    $this->put(route('investigation-orders.update', $this->order), ['priority' => 'stat'])->assertForbidden();

    expect($this->order->fresh()->priority)->toBe('routine');
});

it('does not verify a lab result when only view lab results is held', function () {
    $this->actingAs(labScopeUser(['view lab results']));

    $this->post(route('lab-results.verify', $this->result))->assertForbidden();

    expect($this->result->fresh()->verified_at)->toBeNull();
});

// ── 4. No lab delete: routes, controller methods, permissions are gone ───

it('does not register any laboratory destroy route', function (string $route) {
    expect(Route::has($route))->toBeFalse();
})->with([
    'investigations.destroy',
    'lab-tests.destroy',
    'investigation-orders.destroy',
    'lab-orders.destroy',
    'lab-results.destroy',
    'lab.tests.destroy',
]);

it('has no DELETE route for any laboratory resource URL', function (string $path) {
    $this->actingAs(labScopeUser([
        'view investigations', 'create investigations', 'edit investigations',
        'view investigation orders', 'create investigation orders', 'edit investigation orders',
        'view lab orders', 'create lab orders', 'edit lab orders',
        'view lab results', 'create lab results', 'edit lab results',
    ]));

    $id = match (true) {
        str_contains($path, 'investigation-orders'), str_contains($path, 'lab-orders') => $this->order->id,
        str_contains($path, 'lab-results') => $this->result->id,
        default => $this->labTest->id,
    };

    $this->delete(url("{$path}/{$id}"))->assertStatus(405);

    expect(LabTest::find($this->labTest->id))->not->toBeNull()
        ->and(LabOrder::find($this->order->id))->not->toBeNull()
        ->and(LabResult::find($this->result->id))->not->toBeNull();
})->with([
    'investigations',
    'lab-tests',
    'lab/tests',
    'investigation-orders',
    'lab-orders',
    'lab-results',
]);

it('does not define destroy on the laboratory controllers', function (string $controller) {
    expect(method_exists($controller, 'destroy'))->toBeFalse();
})->with([
    \App\Http\Controllers\InvestigationController::class,
    \App\Http\Controllers\InvestigationOrderController::class,
    \App\Http\Controllers\LabOrderController::class,
    \App\Http\Controllers\LabResultController::class,
]);

it('no longer lists the delete laboratory permissions in the registry or seeder', function (string $permission) {
    expect(PermissionRegistry::all())->not->toContain($permission);
    expect(file_get_contents(database_path('seeders/RolePermissionSeeder.php')))
        ->not->toContain("'{$permission}'");
})->with([
    'delete investigations',
    'delete investigation orders',
    'delete lab orders',
    'delete lab results',
]);

it('keeps view create and edit laboratory permissions in the registry and seeder', function (string $permission) {
    expect(PermissionRegistry::all())->toContain($permission);
    expect(file_get_contents(database_path('seeders/RolePermissionSeeder.php')))->toContain("'{$permission}'");
})->with([
    'view investigations', 'create investigations', 'edit investigations',
    'view investigation orders', 'create investigation orders', 'edit investigation orders',
    'view lab orders', 'create lab orders', 'edit lab orders',
    'view lab results', 'create lab results', 'edit lab results',
]);

// ── 5. UI: no Delete control on lab surfaces ─────────────────────────────

it('shows no Delete order control on the lab order list partial', function () {
    $partial = file_get_contents(resource_path('views/admin/shared/diagnostics/_orders-list.blade.php'));

    expect($partial)->not->toContain("route('investigation-orders.destroy'");
});

it('has no lab delete forms in laboratory blade views', function () {
    $files = glob(resource_path('views/admin/lab/{tests,orders,results}/*.blade.php'), GLOB_BRACE);

    expect($files)->not->toBeEmpty();

    foreach ($files as $file) {
        expect(file_get_contents($file))
            ->not->toContain("@method('DELETE')")
            ->not->toContain('value="DELETE"')
            ->not->toContain('.destroy');
    }
});
