<?php

use App\Models\Medicine;
use App\Models\MedicineBrand;
use App\Models\MedicineCategory;
use App\Models\ModuleRegistry;
use App\Models\PrescriptionInstruction;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

/*
|--------------------------------------------------------------------------
| Pharmacy catalog route scoping (RBAC wave 1, Task 9)
|--------------------------------------------------------------------------
| medicines, medicine-categories, medicine-brands, units and
| prescription-instructions: each action accepts exactly one action
| permission (+ manage pharmacy). Reads also accept the coarse
| view pharmacy | view services (R). units.show and
| prescription-instructions.show are removed (dead: no controller method).
*/

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);

    phCatTenant();

    // Nothing references these records, so every destroy reaches its delete.
    $this->unit = Unit::create([
        'name' => 'PhCat Strip',
        'abbreviation' => 'PCS',
        'conversion_factor' => 1,
        'type' => 'packaging',
        'is_active' => true,
    ]);

    $this->category = MedicineCategory::create([
        'name' => 'PhCat Category',
        'code' => 'PHCAT',
        'is_active' => true,
    ]);

    $this->brand = MedicineBrand::create([
        'name' => 'PhCat Brand',
        'code' => 'PHCATB',
        'is_active' => true,
    ]);

    $this->medicine = Medicine::create([
        'name' => 'PhCat Medicine',
        'sku' => 'PHCAT-MED-001',
        'manage_stock' => false,
        'status' => 'active',
        'selling_price' => 25,
    ]);

    $this->instruction = PrescriptionInstruction::create([
        'title' => 'PhCat Instruction',
        'instruction' => 'Take after meals',
        'is_active' => true,
    ]);
});

function phCatTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    // All catalog routes resolve to `pharmacy.catalog` (CheckModule); the parent
    // and every pharmacy child are enabled so no plan-level 403 can masquerade
    // as a permission 403.
    $modules = ['pharmacy', ...ModuleRegistry::PHARMACY_CHILD_SLUGS];
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, $modules, true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function phCatUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'PhCat Scope User',
        'email' => 'phcat-scope-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

/**
 * The five catalog resources: route prefix => [route parameter, fixture
 * property, permission noun, live read actions].
 */
function phCatResources(): array
{
    return [
        'medicines' => ['medicine', 'medicine', 'medicines', ['index', 'data']],
        'medicine-categories' => ['medicine_category', 'category', 'medicine categories', ['index', 'show']],
        'medicine-brands' => ['medicine_brand', 'brand', 'brands', ['index', 'show']],
        'units' => ['unit', 'unit', 'units', ['index', 'data']],
        'prescription-instructions' => ['prescription_instruction', 'instruction', 'prescriptions', ['index']],
    ];
}

/** HTTP verb per action. */
function phCatMethod(string $action): string
{
    return match ($action) {
        'store' => 'post',
        'update' => 'put',
        'destroy' => 'delete',
        default => 'get',
    };
}

/** The permission verb each action requires after the fix. */
function phCatVerb(string $action): string
{
    return match ($action) {
        'index', 'show', 'data' => 'view',
        'create', 'store' => 'create',
        'edit', 'update' => 'edit',
        'destroy' => 'delete',
    };
}

/** A valid payload for each write, so a request that passes the gate really writes. */
function phCatPayload($test, string $resource, string $action): array
{
    return match ("{$resource}.{$action}") {
        'medicines.store' => ['name' => 'PhCat New Medicine', 'selling_price' => 40, 'status' => 'active', 'manage_stock' => 0],
        'medicines.update' => ['name' => 'PhCat Medicine Renamed', 'sku' => 'PHCAT-MED-001', 'selling_price' => 30, 'status' => 'active', 'manage_stock' => 0],
        'medicine-categories.store' => ['name' => 'PhCat New Category', 'code' => 'PHNEW', 'is_active' => 1],
        'medicine-categories.update' => ['name' => 'PhCat Category Renamed', 'code' => 'PHCAT', 'is_active' => 1],
        'medicine-brands.store' => ['name' => 'PhCat New Brand', 'is_active' => 1],
        'medicine-brands.update' => ['name' => 'PhCat Brand Renamed', 'is_active' => 1],
        'units.store' => ['name' => 'PhCat Vial', 'abbreviation' => 'PCV', 'conversion_factor' => 1, 'type' => 'liquid', 'is_active' => 1],
        'units.update' => ['name' => 'PhCat Strip Renamed', 'abbreviation' => 'PCS', 'conversion_factor' => 1, 'type' => 'packaging', 'is_active' => 1],
        'prescription-instructions.store' => ['title' => 'PhCat New Instruction', 'category' => 'meal', 'instruction' => 'Take before meals', 'is_active' => 1],
        'prescription-instructions.update' => ['title' => 'PhCat Instruction Renamed', 'category' => 'meal', 'instruction' => 'Take with water', 'is_active' => 1],
        default => [],
    };
}

function phCatRequest($test, string $resource, string $action)
{
    [$param, $fixture] = phCatResources()[$resource];

    $params = in_array($action, ['show', 'edit', 'update', 'destroy'], true)
        ? [$param => $test->{$fixture}->id]
        : [];

    $url = route("{$resource}.{$action}", $params);
    $method = phCatMethod($action);

    return $method === 'get'
        ? $test->get($url)
        : $test->{$method}($url, phCatPayload($test, $resource, $action));
}

/** Every live catalog route as [resource, action]. */
function phCatLiveRoutes(): array
{
    $routes = [];
    foreach (phCatResources() as $resource => [, , , $reads]) {
        foreach ([...$reads, 'create', 'store', 'edit', 'update', 'destroy'] as $action) {
            $routes["{$resource}.{$action}"] = [$resource, $action];
        }
    }

    return $routes;
}

function phCatReadRoutes(): array
{
    return array_filter(
        phCatLiveRoutes(),
        fn (array $route) => phCatVerb($route[1]) === 'view'
    );
}

function phCatDenyCases(): array
{
    $cases = [];

    foreach (phCatResources() as $resource => [, , $noun, $reads]) {
        // Coarse read-only permissions never open a form or a write.
        foreach (['view services', 'view pharmacy'] as $coarse) {
            foreach (['create', 'store', 'edit', 'update', 'destroy'] as $action) {
                $cases["{$resource}.{$action} via {$coarse}"] = [$resource, $action, [$coarse]];
            }
        }

        // D2: create does not imply view, edit or delete.
        foreach ([...$reads, 'edit', 'destroy'] as $action) {
            if ($action === 'data') {
                continue;
            }
            $cases["{$resource}.{$action} via create {$noun}"] = [$resource, $action, ["create {$noun}"]];
        }

        // Edit does not imply delete.
        $cases["{$resource}.destroy via edit {$noun}"] = [$resource, 'destroy', ["edit {$noun}"]];
    }

    return $cases;
}

function phCatPositiveCases(): array
{
    $cases = [];

    foreach (phCatLiveRoutes() as $name => [$resource, $action]) {
        $noun = phCatResources()[$resource][2];
        $cases[$name] = [$resource, $action, phCatVerb($action)." {$noun}"];
    }

    return $cases;
}

function phCatAssertPassed($response, string $action): void
{
    if (phCatMethod($action) === 'get') {
        $response->assertOk();

        return;
    }

    // The write ran: a redirect with the success flash, no validation or gate error.
    $response->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('error')
        ->assertSessionHas('success');
}

// ── (a) Deny: a wrong-verb holder that passes today must get 403 ──────────

it('forbids a wrong-verb permission on each catalog route', function (string $resource, string $action, array $permissions) {
    $this->actingAs(phCatUser($permissions));

    phCatRequest($this, $resource, $action)->assertForbidden();
})->with(phCatDenyCases());

// ── (b) Positive control: exactly the new permission passes ───────────────

it('allows exactly the new permission on each catalog route', function (string $resource, string $action, string $permission) {
    $this->actingAs(phCatUser([$permission]));

    phCatAssertPassed(phCatRequest($this, $resource, $action), $action);
})->with(phCatPositiveCases());

it('allows manage pharmacy alone on every catalog route', function (string $resource, string $action) {
    $this->actingAs(phCatUser(['manage pharmacy']));

    phCatAssertPassed(phCatRequest($this, $resource, $action), $action);
})->with(phCatLiveRoutes());

it('allows manage pharmacy alone on each catalog import-status route', function (string $route) {
    $this->actingAs(phCatUser(['manage pharmacy']));

    $this->get(route($route))
        ->assertOk()
        ->assertJson(['status' => 'not_found']);
})->with([
    'medicines.import-status',
    'medicine-categories.import-status',
    'medicine-brands.import-status',
    'units.import-status',
]);

// ── Coarse read: view services / view pharmacy keep every read GET ────────

it('allows the coarse view permissions on every catalog read route', function (string $resource, string $action, string $coarse) {
    $this->actingAs(phCatUser([$coarse]));

    phCatRequest($this, $resource, $action)->assertOk();
})->with(phCatReadRoutes())->with(['view services', 'view pharmacy']);

// ── (c) Side effects: a blocked write writes nothing ──────────────────────
// The "nothing written" assertions run before the status assertion, so that
// before the fix these tests fail because the write actually happened.

it('deletes nothing when view services deletes a catalog record', function (string $resource, string $modelClass) {
    $this->actingAs(phCatUser(['view services']));
    $fixture = phCatResources()[$resource][1];
    $id = $this->{$fixture}->id;

    $response = phCatRequest($this, $resource, 'destroy');

    expect($modelClass::whereKey($id)->exists())->toBeTrue();
    $response->assertForbidden();
})->with([
    'medicine' => ['medicines', Medicine::class],
    'category' => ['medicine-categories', MedicineCategory::class],
    'brand' => ['medicine-brands', MedicineBrand::class],
    'unit' => ['units', Unit::class],
    'instruction' => ['prescription-instructions', PrescriptionInstruction::class],
]);

it('creates no medicine when view pharmacy posts medicines.store', function () {
    $this->actingAs(phCatUser(['view pharmacy']));
    $before = Medicine::count();

    $response = phCatRequest($this, 'medicines', 'store');

    expect(Medicine::count())->toBe($before)
        ->and(Medicine::where('name', 'PhCat New Medicine')->exists())->toBeFalse();
    $response->assertForbidden();
});

it('leaves the brand name unchanged when create brands puts medicine-brands.update', function () {
    $this->actingAs(phCatUser(['create brands']));

    $response = phCatRequest($this, 'medicine-brands', 'update');

    expect($this->brand->fresh()->name)->toBe('PhCat Brand');
    $response->assertForbidden();
});

// ── Dead routes: no controller method behind them ─────────────────────────

it('does not register the dead show routes for units and prescription instructions', function () {
    expect(Route::has('units.show'))->toBeFalse()
        ->and(Route::has('prescription-instructions.show'))->toBeFalse()
        ->and(Route::has('units.index'))->toBeTrue()
        ->and(Route::has('prescription-instructions.index'))->toBeTrue();
});
