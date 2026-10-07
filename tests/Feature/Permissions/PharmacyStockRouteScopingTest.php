<?php

use App\Models\InventoryTransaction;
use App\Models\Medicine;
use App\Models\ModuleRegistry;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Spatie\Permission\Models\Permission;

/*
|--------------------------------------------------------------------------
| Pharmacy stock route scoping (RBAC wave 1, Task 10)
|--------------------------------------------------------------------------
| suppliers, purchases and inventory: each action accepts exactly one action
| permission (+ manage pharmacy, + manage inventory on inventory routes).
| Reads also accept the coarse view pharmacy | view services (R).
*/

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);

    phStkTenant();

    $this->owner = User::create([
        'name' => 'PhStk Owner',
        'email' => 'phstk-owner@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->unit = Unit::create([
        'name' => 'PhStk Tablet',
        'abbreviation' => 'PST',
        'conversion_factor' => 1,
        'type' => 'solid',
        'is_active' => true,
    ]);

    $this->medicine = Medicine::create([
        'name' => 'PhStk Medicine',
        'sku' => 'PHSTK-MED-001',
        'base_unit_id' => $this->unit->id,
        'purchase_unit_id' => $this->unit->id,
        'dispensing_unit_id' => $this->unit->id,
        'manage_stock' => true,
        'status' => 'active',
        'selling_price' => 20,
    ]);

    // The stocked batch: stock-out consumes its remaining_quantity.
    $this->batch = InventoryTransaction::create([
        'medicine_id' => $this->medicine->id,
        'type' => 'stock_in',
        'quantity' => 50,
        'remaining_quantity' => 50,
        'unit_cost' => 10,
        'total_cost' => 500,
        'supplier' => 'PhStk Opening Supplier',
        'batch_no' => 'PHSTK-B1',
        'expiry_date' => now()->addYear(),
        'created_by' => $this->owner->id,
    ]);

    // No transaction names this supplier, so destroy reaches its delete.
    $this->supplier = Supplier::create([
        'name' => 'PhStk Supplier',
        'contact_person' => 'Jane Doe',
        'email' => 'phstk-supplier@example.com',
        'phone' => '03001234567',
        'address' => '1 Test Road',
        'city' => 'Lahore',
        'country' => 'Pakistan',
        'status' => 'active',
    ]);

    $this->pendingOrder = phStkOrder($this, 'pending');
    $this->approvedOrder = phStkOrder($this, 'approved');
});

function phStkTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    // inventory., suppliers. and purchases. resolve to `pharmacy.inventory`
    // (CheckModule); the parent and every pharmacy child are enabled so no
    // plan-level 403 can masquerade as a permission 403.
    $modules = ['pharmacy', ...ModuleRegistry::PHARMACY_CHILD_SLUGS];
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, $modules, true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function phStkUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'PhStk Scope User',
        'email' => 'phstk-scope-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

function phStkOrder($test, string $status): PurchaseOrder
{
    $order = PurchaseOrder::create([
        'supplier_id' => $test->supplier->id,
        'order_date' => now(),
        'status' => $status,
        'created_by' => $test->owner->id,
    ]);

    PurchaseOrderItem::create([
        'purchase_order_id' => $order->id,
        'medicine_id' => $test->medicine->id,
        'unit_id' => $test->unit->id,
        'quantity' => 5,
        'unit_price' => 10,
        'total_price' => 50,
    ]);

    return $order;
}

/**
 * Every live route: name => [HTTP verb, permission it requires after the fix].
 */
function phStkRoutes(): array
{
    return [
        'suppliers.index' => ['get', 'view suppliers'],
        'suppliers.show' => ['get', 'view suppliers'],
        'suppliers.create' => ['get', 'create suppliers'],
        'suppliers.store' => ['post', 'create suppliers'],
        'suppliers.edit' => ['get', 'edit suppliers'],
        'suppliers.update' => ['put', 'edit suppliers'],
        'suppliers.destroy' => ['delete', 'delete suppliers'],

        'purchases.index' => ['get', 'view purchases'],
        'purchases.show' => ['get', 'view purchases'],
        'purchases.create' => ['get', 'create purchases'],
        'purchases.store' => ['post', 'create purchases'],
        'purchases.approve' => ['post', 'edit purchases'],
        'purchases.receive' => ['post', 'edit purchases'],
        'purchases.cancel' => ['post', 'delete purchases'],

        'inventory.index' => ['get', 'view inventory'],
        'inventory.low-stock' => ['get', 'view inventory'],
        'inventory.expiring' => ['get', 'view inventory'],
        'inventory.opening-stock' => ['get', 'view inventory'],
        'inventory.opening-stock.import' => ['post', 'create inventory'],
        'inventory.opening-stock.import-status' => ['get', 'create inventory'],
        'inventory.stock-in' => ['get', 'create inventory'],
        'inventory.process-stock-in' => ['post', 'create inventory'],
        'inventory.stock-out' => ['get', 'edit inventory'],
        'inventory.process-stock-out' => ['post', 'edit inventory'],
        'inventory.medicines.batches' => ['get', 'create inventory'],
    ];
}

function phStkRouteParams($test, string $name): array
{
    return match ($name) {
        'suppliers.show', 'suppliers.edit', 'suppliers.update', 'suppliers.destroy' => ['supplier' => $test->supplier->id],
        'purchases.show', 'purchases.approve', 'purchases.cancel' => ['purchase' => $test->pendingOrder->id],
        'purchases.receive' => ['purchase' => $test->approvedOrder->id],
        'inventory.medicines.batches' => ['medicine' => $test->medicine->id],
        default => [],
    };
}

/** A valid payload for each write, so a request that passes the gate really writes. */
function phStkPayload($test, string $name): array
{
    return match ($name) {
        'suppliers.store' => [
            'name' => 'PhStk New Supplier', 'contact_person' => 'John Roe', 'email' => 'phstk-new@example.com',
            'phone' => '03007654321', 'address' => '2 Test Road', 'city' => 'Lahore', 'country' => 'Pakistan',
            'status' => 'active',
        ],
        'suppliers.update' => [
            'name' => 'PhStk Supplier Renamed', 'contact_person' => 'Jane Doe', 'email' => 'phstk-supplier@example.com',
            'phone' => '03001234567', 'address' => '1 Test Road', 'city' => 'Lahore', 'country' => 'Pakistan',
            'status' => 'active',
        ],
        'purchases.store' => [
            'supplier_id' => $test->supplier->id,
            'order_date' => now()->toDateString(),
            'expected_delivery' => now()->addWeek()->toDateString(),
            'notes' => 'PhStk order',
            'items' => [[
                'medicine_id' => $test->medicine->id, 'unit_id' => $test->unit->id,
                'quantity' => 3, 'unit_price' => 12,
            ]],
        ],
        'inventory.process-stock-in' => [
            'stock_in_mode' => 'new',
            'medicine_id' => $test->medicine->id,
            'quantity' => 10,
            'unit_id' => $test->unit->id,
            'unit_cost' => 11,
            'batch_no' => 'PHSTK-B2',
            'expiry_date' => now()->addYear()->toDateString(),
            'supplier' => 'PhStk Restock Supplier',
        ],
        'inventory.process-stock-out' => [
            'medicine_id' => $test->medicine->id,
            'quantity' => 5,
            'reason' => 'damaged',
        ],
        default => [],
    };
}

function phStkRequest($test, string $name)
{
    [$method] = phStkRoutes()[$name];
    $url = route($name, phStkRouteParams($test, $name));

    return $method === 'get'
        ? $test->get($url)
        : $test->{$method}($url, phStkPayload($test, $name));
}

function phStkReadRoutes(): array
{
    return [
        'suppliers.index', 'suppliers.show',
        'purchases.index', 'purchases.show',
        'inventory.index', 'inventory.low-stock', 'inventory.expiring', 'inventory.opening-stock',
    ];
}

function phStkInventoryRoutes(): array
{
    return array_values(array_filter(
        array_keys(phStkRoutes()),
        fn (string $name) => str_starts_with($name, 'inventory.')
    ));
}

function phStkDenyCases(): array
{
    $cases = [];

    // Coarse read-only permissions never open a form or a write.
    $writes = [
        'suppliers.create', 'suppliers.store', 'suppliers.edit', 'suppliers.update', 'suppliers.destroy',
        'purchases.create', 'purchases.store', 'purchases.approve', 'purchases.receive', 'purchases.cancel',
        'inventory.stock-in', 'inventory.process-stock-in', 'inventory.stock-out', 'inventory.process-stock-out',
        'inventory.medicines.batches', 'inventory.opening-stock.import', 'inventory.opening-stock.import-status',
    ];
    foreach ($writes as $name) {
        $cases["{$name} via view services"] = [$name, ['view services']];
    }
    foreach (['suppliers.create', 'suppliers.store', 'suppliers.edit', 'suppliers.update', 'suppliers.destroy',
        'purchases.create', 'purchases.store'] as $name) {
        $cases["{$name} via view pharmacy"] = [$name, ['view pharmacy']];
    }

    // D2: create does not imply view, edit or delete.
    foreach (['suppliers.index', 'suppliers.edit', 'suppliers.destroy'] as $name) {
        $cases["{$name} via create suppliers"] = [$name, ['create suppliers']];
    }
    $cases['purchases.index via create purchases'] = ['purchases.index', ['create purchases']];
    $cases['purchases.approve via create purchases'] = ['purchases.approve', ['create purchases']];
    $cases['inventory.index via create inventory'] = ['inventory.index', ['create inventory']];

    // Edit does not imply delete.
    $cases['purchases.cancel via edit purchases'] = ['purchases.cancel', ['edit purchases']];

    // Stock-in (create inventory) and stock-out (edit inventory) are separate actions.
    foreach (['inventory.stock-out', 'inventory.process-stock-out'] as $name) {
        $cases["{$name} via create inventory"] = [$name, ['create inventory']];
    }
    // The batch lookup serves the stock-in form only (corrected 2026-10-07).
    foreach (['inventory.stock-in', 'inventory.process-stock-in', 'inventory.medicines.batches', 'inventory.opening-stock.import'] as $name) {
        $cases["{$name} via edit inventory"] = [$name, ['edit inventory']];
    }

    return $cases;
}

function phStkPositiveCases(): array
{
    $cases = [];
    foreach (phStkRoutes() as $name => [, $permission]) {
        $cases["{$name} via {$permission}"] = [$name, $permission];
    }

    return $cases;
}

function phStkAssertPassed($response, string $name): void
{
    [$method] = phStkRoutes()[$name];

    if ($name === 'inventory.opening-stock.import-status') {
        $response->assertOk()->assertJson(['status' => 'not_found']);

        return;
    }

    if ($name === 'inventory.opening-stock.import') {
        // Past the gate, the request reaches the controller's file validation.
        $response->assertRedirect()->assertSessionHasErrors('file');

        return;
    }

    if ($method === 'get') {
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

it('forbids a wrong-verb permission on each stock route', function (string $name, array $permissions) {
    $this->actingAs(phStkUser($permissions));

    phStkRequest($this, $name)->assertForbidden();
})->with(phStkDenyCases());

// ── (b) Positive control: exactly the new permission passes ───────────────

it('allows exactly the new permission on each stock route', function (string $name, string $permission) {
    $this->actingAs(phStkUser([$permission]));

    phStkAssertPassed(phStkRequest($this, $name), $name);
})->with(phStkPositiveCases());

it('allows manage pharmacy alone on every supplier, purchase and inventory route', function (string $name) {
    $this->actingAs(phStkUser(['manage pharmacy']));

    phStkAssertPassed(phStkRequest($this, $name), $name);
})->with(array_keys(phStkRoutes()));

it('allows manage inventory alone on every inventory route', function (string $name) {
    $this->actingAs(phStkUser(['manage inventory']));

    phStkAssertPassed(phStkRequest($this, $name), $name);
})->with(phStkInventoryRoutes());

// ── Coarse read: view services / view pharmacy keep every read GET ────────

it('allows the coarse view permissions on every stock read route', function (string $name, string $coarse) {
    $this->actingAs(phStkUser([$coarse]));

    phStkRequest($this, $name)->assertOk();
})->with(phStkReadRoutes())->with(['view services', 'view pharmacy']);

// ── (c) Side effects: a blocked write writes nothing ──────────────────────
// The "nothing written" assertions run before the status assertion, so that
// before the fix these tests fail because the write actually happened.

it('leaves a pending order pending when view services approves it', function () {
    $this->actingAs(phStkUser(['view services']));

    $response = phStkRequest($this, 'purchases.approve');

    expect($this->pendingOrder->fresh()->status)->toBe('pending');
    $response->assertForbidden();
});

it('receives nothing when view services receives an approved order', function () {
    $this->actingAs(phStkUser(['view services']));
    $transactionsBefore = InventoryTransaction::count();
    $stockInBefore = InventoryTransaction::where('type', 'stock_in')->count();
    $stockBefore = $this->medicine->fresh()->getTotalAvailableStock();

    $response = phStkRequest($this, 'purchases.receive');

    expect($this->approvedOrder->fresh()->status)->toBe('approved')
        ->and(InventoryTransaction::count())->toBe($transactionsBefore)
        ->and(InventoryTransaction::where('type', 'stock_in')->count())->toBe($stockInBefore)
        ->and($this->batch->fresh()->remaining_quantity)->toBe(50)
        ->and($this->medicine->fresh()->getTotalAvailableStock())->toBe($stockBefore);
    $response->assertForbidden();
});

it('leaves the order status unchanged when view services cancels it', function () {
    $this->actingAs(phStkUser(['view services']));

    $response = phStkRequest($this, 'purchases.cancel');

    expect($this->pendingOrder->fresh()->status)->toBe('pending');
    $response->assertForbidden();
});

it('adds no batch when view services posts stock-in', function () {
    $this->actingAs(phStkUser(['view services']));
    $transactionsBefore = InventoryTransaction::count();

    $response = phStkRequest($this, 'inventory.process-stock-in');

    expect(InventoryTransaction::count())->toBe($transactionsBefore)
        ->and(InventoryTransaction::where('batch_no', 'PHSTK-B2')->exists())->toBeFalse();
    $response->assertForbidden();
});

it('leaves the batch quantity unchanged when view services posts stock-out', function () {
    $this->actingAs(phStkUser(['view services']));
    $transactionsBefore = InventoryTransaction::count();

    $response = phStkRequest($this, 'inventory.process-stock-out');

    expect($this->batch->fresh()->remaining_quantity)->toBe(50)
        ->and(InventoryTransaction::count())->toBe($transactionsBefore)
        ->and(InventoryTransaction::where('type', 'stock_out')->exists())->toBeFalse();
    $response->assertForbidden();
});

it('keeps the supplier when view pharmacy deletes it', function () {
    $this->actingAs(phStkUser(['view pharmacy']));

    $response = phStkRequest($this, 'suppliers.destroy');

    expect(Supplier::whereKey($this->supplier->id)->exists())->toBeTrue();
    $response->assertForbidden();
});

// Proves the receive side-effect assertion is meaningful: a permitted receive writes.
it('creates inventory transactions when edit purchases receives an approved order', function () {
    $this->actingAs(phStkUser(['edit purchases']));
    $transactionsBefore = InventoryTransaction::count();

    phStkRequest($this, 'purchases.receive')
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($this->approvedOrder->fresh()->status)->toBe('received')
        ->and(InventoryTransaction::count())->toBe($transactionsBefore + 1)
        ->and(InventoryTransaction::where('reference_no', $this->approvedOrder->po_number)->value('quantity'))->toBe(5);
});

// Proves the stock-out side-effect assertion is meaningful: a permitted stock-out consumes the batch.
it('decrements the batch when edit inventory posts stock-out', function () {
    $this->actingAs(phStkUser(['edit inventory']));

    // AD-1: edit inventory alone cannot view the index, so it returns to the stock-out form.
    phStkRequest($this, 'inventory.process-stock-out')
        ->assertRedirect(route('inventory.stock-out'))
        ->assertSessionHas('success');

    expect($this->batch->fresh()->remaining_quantity)->toBe(45);
});

// ── AD-1 redirects (Task 12): a write lands on the index only when the user can view it ──

/** [write route, write permission, index route, a view permission, fallback route or null for back()]. */
function phStkRedirectCases(): array
{
    return [
        'suppliers.store' => ['suppliers.store', 'create suppliers', 'suppliers.index', 'view suppliers', 'suppliers.create'],
        'suppliers.update' => ['suppliers.update', 'edit suppliers', 'suppliers.index', 'view suppliers', 'suppliers.edit'],
        'suppliers.destroy' => ['suppliers.destroy', 'delete suppliers', 'suppliers.index', 'view suppliers', null],
        'purchases.store' => ['purchases.store', 'create purchases', 'purchases.index', 'view purchases', 'purchases.create'],
        'inventory.process-stock-in' => ['inventory.process-stock-in', 'create inventory', 'inventory.index', 'view inventory', 'inventory.stock-in'],
        'inventory.process-stock-out' => ['inventory.process-stock-out', 'edit inventory', 'inventory.index', 'view inventory', 'inventory.stock-out'],
    ];
}

it('redirects a stock write to its fallback when the user cannot view the index', function (string $name, string $write, string $index, string $view, ?string $fallback) {
    $this->actingAs(phStkUser([$write]));
    $previous = url('/dashboard?from=phstk-write');
    $this->from($previous);

    $expected = match (true) {
        $fallback === null => $previous,
        $fallback === 'suppliers.edit' => route('suppliers.edit', ['supplier' => $this->supplier->id]),
        default => route($fallback),
    };

    phStkRequest($this, $name)
        ->assertRedirect($expected)
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');
})->with(phStkRedirectCases());

it('redirects a stock write to the index when the user can view it', function (string $name, string $write, string $index, string $view) {
    $this->actingAs(phStkUser([$write, $view]));
    $this->from(url('/dashboard?from=phstk-write'));

    phStkRequest($this, $name)
        ->assertRedirect(route($index))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');
})->with(phStkRedirectCases());

it('counts manage inventory as able to view the inventory index after a stock-in', function () {
    $this->actingAs(phStkUser(['create inventory', 'manage inventory']));

    phStkRequest($this, 'inventory.process-stock-in')
        ->assertRedirect(route('inventory.index'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');
});

// ── UI gating (Task 13): write controls render only for permitted users ───

/**
 * Control name => the permissions that reveal it. Fixture-free, so it can feed datasets.
 */
function phStkUiControlPermissions(): array
{
    $mpMi = ['manage pharmacy', 'manage inventory'];

    return [
        'suppliers new' => ['create suppliers', 'manage pharmacy'],
        'suppliers row edit' => ['edit suppliers', 'manage pharmacy'],
        'suppliers row delete' => ['delete suppliers', 'manage pharmacy'],
        'suppliers show edit' => ['edit suppliers', 'manage pharmacy'],
        'purchases new' => ['create purchases', 'manage pharmacy'],
        'purchases index approve' => ['edit purchases', 'manage pharmacy'],
        'purchases index receive' => ['edit purchases', 'manage pharmacy'],
        'purchases index cancel' => ['delete purchases', 'manage pharmacy'],
        'purchases show approve' => ['edit purchases', 'manage pharmacy'],
        'purchases show receive' => ['edit purchases', 'manage pharmacy'],
        'purchases show cancel' => ['delete purchases', 'manage pharmacy'],
        'inventory index stock in' => ['create inventory', ...$mpMi],
        'inventory index stock out' => ['edit inventory', ...$mpMi],
        'low-stock stock in' => ['create inventory', ...$mpMi],
        'opening-stock stock in' => ['create inventory', ...$mpMi],
        'expiring stock out' => ['edit inventory', ...$mpMi],
    ];
}

/**
 * Control name => [page route, page params, exact markup].
 * Stock links on low-stock and expiring carry a ?medicine= query.
 */
function phStkUiControls($test): array
{
    $stockIn = 'href="'.route('inventory.stock-in');
    $stockOut = 'href="'.route('inventory.stock-out');
    $pending = ['purchase' => $test->pendingOrder->id];
    $approved = ['purchase' => $test->approvedOrder->id];

    return [
        'suppliers new' => ['suppliers.index', [], 'href="'.route('suppliers.create').'"'],
        'suppliers row edit' => ['suppliers.index', [], 'href="'.route('suppliers.edit', $test->supplier).'"'],
        'suppliers row delete' => ['suppliers.index', [], 'action="'.route('suppliers.destroy', $test->supplier).'"'],
        'suppliers show edit' => ['suppliers.show', ['supplier' => $test->supplier->id], 'href="'.route('suppliers.edit', $test->supplier).'"'],
        'purchases new' => ['purchases.index', [], 'href="'.route('purchases.create').'"'],
        'purchases index approve' => ['purchases.index', [], 'action="'.route('purchases.approve', $test->pendingOrder).'"'],
        'purchases index receive' => ['purchases.index', [], 'action="'.route('purchases.receive', $test->approvedOrder).'"'],
        'purchases index cancel' => ['purchases.index', [], 'action="'.route('purchases.cancel', $test->pendingOrder).'"'],
        'purchases show approve' => ['purchases.show', $pending, 'action="'.route('purchases.approve', $test->pendingOrder).'"'],
        'purchases show receive' => ['purchases.show', $approved, 'action="'.route('purchases.receive', $test->approvedOrder).'"'],
        'purchases show cancel' => ['purchases.show', $pending, 'action="'.route('purchases.cancel', $test->pendingOrder).'"'],
        'inventory index stock in' => ['inventory.index', [], $stockIn.'"'],
        'inventory index stock out' => ['inventory.index', [], $stockOut.'"'],
        'low-stock stock in' => ['inventory.low-stock', [], $stockIn.'?medicine='.$test->lowMedicine->id.'"'],
        'opening-stock stock in' => ['inventory.opening-stock', [], $stockIn.'"'],
        'expiring stock out' => ['inventory.expiring', [], $stockOut.'?medicine='.$test->medicine->id.'"'],
    ];
}

/** The view permission that opens each control's page. */
function phStkUiViewPermission(string $pageRoute): string
{
    return match (strtok($pageRoute, '.')) {
        'suppliers' => 'view suppliers',
        'purchases' => 'view purchases',
        'inventory' => 'view inventory',
    };
}

/** Seed what makes the low-stock, expiring and locked opening-stock pages render their controls. */
function phStkUiSeedStockPages($test): void
{
    // No stock at all: at or below a zero reorder level, so it lists as low stock.
    $test->lowMedicine = Medicine::create([
        'name' => 'PhStk Low Medicine',
        'sku' => 'PHSTK-MED-LOW',
        'base_unit_id' => $test->unit->id,
        'manage_stock' => true,
        'status' => 'active',
        'selling_price' => 20,
    ]);

    // Expires within 30 days: the expiring page offers its Remove (stock-out) link.
    InventoryTransaction::create([
        'medicine_id' => $test->medicine->id,
        'type' => 'stock_in',
        'quantity' => 5,
        'remaining_quantity' => 5,
        'unit_cost' => 10,
        'total_cost' => 50,
        'batch_no' => 'PHSTK-EXP',
        'expiry_date' => now()->addDays(10),
        'created_by' => $test->owner->id,
    ]);

    // A completed opening-stock import: the locked page points to Stock In.
    \App\Services\OpeningStockService::lock($test->owner->id, 1);
}

function phStkUiControlCases(): array
{
    $cases = [];
    foreach (phStkUiControlPermissions() as $control => $permissions) {
        foreach ($permissions as $permission) {
            $cases["{$control} via {$permission}"] = [$control, $permission];
        }
    }

    return $cases;
}

it('hides every stock write control from a view-only user', function (string $control) {
    phStkUiSeedStockPages($this);
    [$pageRoute, $params, $markup] = phStkUiControls($this)[$control];
    $this->actingAs(phStkUser([phStkUiViewPermission($pageRoute)]));

    $this->get(route($pageRoute, $params))
        ->assertOk()
        ->assertDontSee($markup, false);
})->with(array_keys(phStkUiControlPermissions()));

it('shows a stock write control to each permission that may use it', function (string $control, string $permission) {
    phStkUiSeedStockPages($this);
    [$pageRoute, $params, $markup] = phStkUiControls($this)[$control];
    $this->actingAs(phStkUser([phStkUiViewPermission($pageRoute), $permission]));

    $this->get(route($pageRoute, $params))
        ->assertOk()
        ->assertSee($markup, false);
})->with(phStkUiControlCases());

it('hides a stock write control from a holder of a different write permission', function (string $control, string $permission) {
    phStkUiSeedStockPages($this);
    [$pageRoute, $params, $markup] = phStkUiControls($this)[$control];
    $this->actingAs(phStkUser([phStkUiViewPermission($pageRoute), $permission]));

    $this->get(route($pageRoute, $params))
        ->assertOk()
        ->assertDontSee($markup, false);
})->with([
    'purchases new via edit purchases' => ['purchases new', 'edit purchases'],
    'purchases index approve via delete purchases' => ['purchases index approve', 'delete purchases'],
    'purchases index cancel via edit purchases' => ['purchases index cancel', 'edit purchases'],
    'purchases show receive via delete purchases' => ['purchases show receive', 'delete purchases'],
    'purchases show cancel via edit purchases' => ['purchases show cancel', 'edit purchases'],
    'suppliers row delete via edit suppliers' => ['suppliers row delete', 'edit suppliers'],
    'inventory index stock in via edit inventory' => ['inventory index stock in', 'edit inventory'],
    'inventory index stock out via create inventory' => ['inventory index stock out', 'create inventory'],
    'low-stock stock in via edit inventory' => ['low-stock stock in', 'edit inventory'],
    'expiring stock out via create inventory' => ['expiring stock out', 'create inventory'],
]);

it('keeps the opening-stock import gate on manage pharmacy or manage inventory', function (array $permissions, bool $visible) {
    $this->actingAs(phStkUser($permissions));

    $importForm = 'action="'.route('inventory.opening-stock.import').'"';
    $response = $this->get(route('inventory.opening-stock'))->assertOk();

    $visible
        ? $response->assertSee($importForm, false)->assertSee('data-opening-stock-import-trigger', false)
        : $response->assertDontSee($importForm, false)->assertDontSee('data-opening-stock-import-trigger', false);
})->with([
    'manage pharmacy' => [['manage pharmacy'], true],
    'view inventory only' => [['view inventory'], false],
]);
