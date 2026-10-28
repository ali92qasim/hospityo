<?php

use App\Models\ImagingOrder;
use App\Models\ImagingReport;
use App\Models\ImagingStudy;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Support\PermissionRegistry;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

/*
|--------------------------------------------------------------------------
| Imaging route scoping (RBAC wave 3 remainder, Task 5)
|--------------------------------------------------------------------------
| /imaging/studies* and /imaging/orders* use the dedicated imaging studies /
| imaging orders permissions only (no investigations / lab orders fallback).
| Radiology results keep view / create / edit. There is no imaging delete:
| every destroy route and delete radiology results are gone.
*/

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
    $this->withoutVite();

    imagingScopeTenant();

    $this->creator = imagingScopeUser([]);

    $patient = Patient::create([
        'name' => 'Imaging Scope Patient',
        'gender' => 'male',
        'age' => 30,
        'phone' => '03001234567',
    ]);

    $this->study = ImagingStudy::create([
        'code' => 'IS'.uniqid(),
        'name' => 'Scope Chest X-Ray',
        'category' => 'x-ray',
        'price' => 500,
        'turnaround_time' => 4,
        'is_active' => true,
    ]);
    $this->order = ImagingOrder::create([
        'patient_id' => $patient->id,
        'priority' => 'routine',
        'status' => 'ordered',
        'ordered_at' => now(),
    ]);
    $this->report = ImagingReport::create([
        'imaging_order_id' => $this->order->id,
        'report_text' => 'Normal',
        'impression' => 'Normal',
        'status' => 'draft',
    ]);
});

function imagingScopeTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')->andReturn(true);

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function imagingScopeUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Imaging Scope User',
        'email' => 'imaging-scope-user-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

function imagingScopeUrl($test, string $route): string
{
    $parameters = [];
    foreach (Route::getRoutes()->getByName($route)->parameterNames() as $name) {
        $parameters[$name] = match ($name) {
            'imagingStudy' => $test->study->id,
            'imagingOrder' => $test->order->id,
            'radiologyResult' => $test->report->id,
        };
    }

    return route($route, $parameters);
}

function imagingScopeRequest($test, string $method, string $route)
{
    $url = imagingScopeUrl($test, $route);

    return $method === 'get' ? $test->get($url) : $test->{$method}($url, []);
}

/** @return array<string, array{0: string, 1: string, 2: string}> [method, route, permission] */
function imagingScopeRoutes(): array
{
    $routes = [];

    foreach ([
        // Studies catalog
        ['get', 'imaging.studies.index', 'view imaging studies'],
        ['get', 'imaging.studies.data', 'view imaging studies'],
        ['get', 'imaging.studies.show', 'view imaging studies'],
        ['get', 'imaging.studies.create', 'create imaging studies'],
        ['post', 'imaging.studies.store', 'create imaging studies'],
        ['post', 'imaging.studies.import', 'create imaging studies'],
        ['get', 'imaging.studies.import-status', 'create imaging studies'],
        ['get', 'imaging.studies.edit', 'edit imaging studies'],
        ['put', 'imaging.studies.update', 'edit imaging studies'],
        // Orders
        ['get', 'imaging.orders.index', 'view imaging orders'],
        ['get', 'imaging.orders.show', 'view imaging orders'],
        ['get', 'imaging.orders.create', 'create imaging orders'],
        ['post', 'imaging.orders.store', 'create imaging orders'],
        ['get', 'imaging.orders.edit', 'edit imaging orders'],
        ['put', 'imaging.orders.update', 'edit imaging orders'],
        // Radiology results / reports
        ['get', 'imaging.reports.index', 'view radiology results'],
        ['get', 'radiology-results.index', 'view radiology results'],
        ['get', 'radiology-results.show', 'view radiology results'],
        ['get', 'radiology-results.create', 'create radiology results'],
        ['post', 'radiology-results.store', 'create radiology results'],
        ['get', 'radiology-results.edit', 'edit radiology results'],
        ['put', 'radiology-results.update', 'edit radiology results'],
        ['patch', 'radiology-results.update', 'edit radiology results'],
    ] as [$method, $route, $permission]) {
        $routes["{$method} {$route} ({$permission})"] = [$method, $route, $permission];
    }

    return $routes;
}

dataset('imaging routes', fn () => imagingScopeRoutes());

dataset('imaging studies and orders routes', fn () => array_filter(
    imagingScopeRoutes(),
    fn (array $row) => str_starts_with($row[1], 'imaging.studies') || str_starts_with($row[1], 'imaging.orders'),
));

// ── 1. Deny: wrong-verb holders must get 403 ────────────────────────────────

it('forbids wrong-verb permissions on each imaging route', function (string $method, string $route, string $permission) {
    $verb = strtok($permission, ' ');
    $subject = substr($permission, strlen($verb) + 1);

    $wrong = collect(['view', 'create', 'edit'])
        ->reject(fn ($v) => $v === $verb)
        ->map(fn ($v) => "{$v} {$subject}")
        ->all();

    $this->actingAs(imagingScopeUser($wrong));

    imagingScopeRequest($this, $method, $route)->assertForbidden();
})->with('imaging routes');

it('forbids lab investigation / lab order permissions on every studies and orders route', function (string $method, string $route, string $permission) {
    $this->actingAs(imagingScopeUser([
        'view investigations', 'create investigations', 'edit investigations',
        'view investigation orders', 'create investigation orders', 'edit investigation orders',
        'view lab orders', 'create lab orders', 'edit lab orders',
        'view lab results', 'create lab results', 'edit lab results',
    ]));

    imagingScopeRequest($this, $method, $route)->assertForbidden();
})->with('imaging studies and orders routes');

it('forbids a user with only imaging studies/orders permissions on the other subject', function () {
    $this->actingAs(imagingScopeUser(['view imaging studies', 'create imaging studies', 'edit imaging studies']));
    $this->get(route('imaging.orders.index'))->assertForbidden();

    $this->actingAs(imagingScopeUser(['view imaging orders', 'create imaging orders', 'edit imaging orders']));
    $this->get(route('imaging.studies.index'))->assertForbidden();
});

it('forbids a user with only delete-style imaging permissions on every imaging route', function (string $method, string $route, string $permission) {
    $this->actingAs(imagingScopeUser([
        'delete investigations',
        'delete investigation orders',
        'delete lab orders',
        'delete radiology results',
    ]));

    imagingScopeRequest($this, $method, $route)->assertForbidden();
})->with('imaging routes');

// ── 2. Positive control: exactly the matching permission passes ─────────────

it('allows exactly the matching permission on each imaging route', function (string $method, string $route, string $permission) {
    $this->actingAs(imagingScopeUser([$permission]));

    $response = imagingScopeRequest($this, $method, $route);

    expect($response->getStatusCode())->not->toBe(403)->not->toBe(404);
})->with('imaging routes');

// ── 3. Side effects: a blocked write writes nothing ─────────────────────────

it('creates no imaging study when only view imaging studies posts store', function () {
    $this->actingAs(imagingScopeUser(['view imaging studies']));
    $count = ImagingStudy::count();

    $this->post(route('imaging.studies.store'), [
        'code' => 'NOPE', 'name' => 'Nope', 'category' => 'x-ray', 'price' => 1, 'turnaround_time' => 1,
    ])->assertForbidden();

    expect(ImagingStudy::count())->toBe($count);
});

it('leaves the imaging order untouched when view imaging orders puts update', function () {
    $this->actingAs(imagingScopeUser(['view imaging orders']));

    $this->put(route('imaging.orders.update', $this->order), ['priority' => 'stat'])->assertForbidden();

    expect($this->order->fresh()->priority)->toBe('routine');
});

it('leaves the radiology result untouched when only view radiology results puts update', function () {
    $this->actingAs(imagingScopeUser(['view radiology results']));

    $this->put(route('radiology-results.update', $this->report), ['status' => 'final'])->assertForbidden();

    expect($this->report->fresh()->status)->toBe('draft');
});

// ── 4. No imaging delete: routes, controller methods, permissions are gone ──

it('does not register any imaging destroy route', function (string $route) {
    expect(Route::has($route))->toBeFalse();
})->with([
    'imaging.studies.destroy',
    'imaging.orders.destroy',
    'radiology-results.destroy',
]);

it('has no DELETE route for any imaging resource URL', function (string $path) {
    $this->actingAs(imagingScopeUser([
        'view imaging studies', 'create imaging studies', 'edit imaging studies',
        'view imaging orders', 'create imaging orders', 'edit imaging orders',
        'view radiology results', 'create radiology results', 'edit radiology results',
    ]));

    $id = match (true) {
        str_contains($path, 'orders') => $this->order->id,
        str_contains($path, 'radiology-results') => $this->report->id,
        default => $this->study->id,
    };

    $this->delete(url("{$path}/{$id}"))->assertStatus(405);

    expect(ImagingStudy::find($this->study->id))->not->toBeNull()
        ->and(ImagingOrder::find($this->order->id))->not->toBeNull()
        ->and(ImagingReport::find($this->report->id))->not->toBeNull();
})->with([
    'imaging/studies',
    'imaging/orders',
    'radiology-results',
]);

it('does not define destroy on the imaging controllers', function (string $controller) {
    expect(method_exists($controller, 'destroy'))->toBeFalse();
})->with([
    \App\Http\Controllers\ImagingStudyController::class,
    \App\Http\Controllers\ImagingOrderController::class,
    \App\Http\Controllers\RadiologyResultController::class,
]);

it('no longer lists delete radiology results in the registry or seeder', function () {
    expect(PermissionRegistry::all())->not->toContain('delete radiology results');
    expect(file_get_contents(database_path('seeders/RolePermissionSeeder.php')))
        ->not->toContain("'delete radiology results'");
});

it('lists the new imaging permissions and keeps radiology results view create edit', function (string $permission) {
    expect(PermissionRegistry::all())->toContain($permission);
    expect(file_get_contents(database_path('seeders/RolePermissionSeeder.php')))->toContain("'{$permission}'");
})->with([
    'view imaging studies', 'create imaging studies', 'edit imaging studies',
    'view imaging orders', 'create imaging orders', 'edit imaging orders',
    'view radiology results', 'create radiology results', 'edit radiology results',
]);

// ── 5. UI: no Delete control on imaging surfaces ────────────────────────────

it('shows no Delete order control on the imaging order list partial', function () {
    $partial = file_get_contents(resource_path('views/admin/shared/diagnostics/_orders-list.blade.php'));

    expect($partial)
        ->not->toContain("route('imaging.orders.destroy'")
        ->not->toContain("@method('DELETE')");
});

it('has no delete forms in imaging or radiology blade views', function () {
    $files = array_merge(
        glob(resource_path('views/admin/imaging/{studies,orders,reports}/*.blade.php'), GLOB_BRACE),
        glob(resource_path('views/admin/radiology/results/*.blade.php')),
    );

    expect($files)->not->toBeEmpty();

    foreach ($files as $file) {
        expect(file_get_contents($file))
            ->not->toContain("@method('DELETE')")
            ->not->toContain('value="DELETE"')
            ->not->toContain('.destroy');
    }
});

it('renders no delete control in the shared catalog datatable script', function () {
    expect(file_get_contents(resource_path('js/pagination.js')))
        ->not->toContain('value="DELETE"')
        ->not->toContain('fa-trash');
});
