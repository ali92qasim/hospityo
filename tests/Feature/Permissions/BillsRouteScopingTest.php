<?php

use App\Models\Bill;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Support\PermissionRegistry;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

/*
|--------------------------------------------------------------------------
| Bills route scoping (RBAC wave 3 remainder, Task 3)
|--------------------------------------------------------------------------
| Each bills HTTP route accepts exactly one action permission. There is no
| bill delete: the destroy route and `delete bills` permission are gone.
| Payment routes keep create/edit/delete payments exactly as before.
*/

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
    $this->withoutVite();

    billScopeTenant();

    $this->patient = Patient::create([
        'name' => 'Bill Scope Patient',
        'gender' => 'male',
        'age' => 30,
        'phone' => '03001234567',
    ]);
    $this->creator = billScopeUser([]);
    $this->bill = billScopeBill($this->patient, $this->creator);
    $this->payment = billScopePayment($this->bill, $this->creator);
});

function billScopeTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')->andReturn(true);

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function billScopeUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Bills Scope User',
        'email' => 'bill-scope-user-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

function billScopeBill(Patient $patient, User $creator): Bill
{
    return Bill::create([
        'patient_id' => $patient->id,
        'bill_number' => 'BILL-SCOPE-'.uniqid(),
        'bill_date' => now(),
        'bill_type' => 'opd',
        'subtotal' => 1000,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 1000,
        'paid_amount' => 200,
        'due_amount' => 800,
        'status' => 'partial',
        'created_by' => $creator->id,
    ]);
}

function billScopePayment(Bill $bill, User $user): Payment
{
    return Payment::create([
        'bill_id' => $bill->id,
        'payment_date' => now(),
        'amount' => 200,
        'payment_method' => 'cash',
        'received_by' => $user->id,
    ]);
}

function billScopePayload(string $route, Patient $patient): array
{
    return match ($route) {
        'bills.store', 'bills.update' => [
            'patient_id' => $patient->id,
            'bill_date' => now()->toDateString(),
            'bill_type' => 'opd',
            'items' => [[
                'description' => 'Scope Item',
                'quantity' => 1,
                'unit_price' => 1000,
            ]],
        ],
        'bills.add-payment' => [
            'amount' => 100,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ],
        'bills.update-payment' => [
            'amount' => 150,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ],
        default => [],
    };
}

function billScopeUrl(string $route, Bill $bill, Payment $payment): string
{
    return match ($route) {
        'bills.show', 'bills.edit', 'bills.update', 'bills.print', 'bills.add-payment' => route($route, ['bill' => $bill->id]),
        'bills.update-payment', 'bills.remove-payment' => route($route, ['bill' => $bill->id, 'payment' => $payment->id]),
        default => route($route),
    };
}

function billScopeRequest($test, string $method, string $route, Bill $bill, Payment $payment, Patient $patient)
{
    $url = billScopeUrl($route, $bill, $payment);

    return $method === 'get'
        ? $test->get($url)
        : $test->{$method}($url, billScopePayload($route, $patient));
}

// ── 1. Deny: wrong-verb holder must get 403 ──────────────────────────────

dataset('bills escalations', [
    'index via create only' => ['get', 'bills.index', ['create bills']],
    'index via edit only' => ['get', 'bills.index', ['edit bills']],
    'data via create only' => ['get', 'bills.data', ['create bills']],
    'data via edit only' => ['get', 'bills.data', ['edit bills']],
    'show via create only' => ['get', 'bills.show', ['create bills']],
    'show via edit only' => ['get', 'bills.show', ['edit bills']],
    'print via create only' => ['get', 'bills.print', ['create bills']],
    'print via edit only' => ['get', 'bills.print', ['edit bills']],
    'create via view only' => ['get', 'bills.create', ['view bills']],
    'create via edit only' => ['get', 'bills.create', ['edit bills']],
    'store via view only' => ['post', 'bills.store', ['view bills']],
    'store via edit only' => ['post', 'bills.store', ['edit bills']],
    'edit via view only' => ['get', 'bills.edit', ['view bills']],
    'edit via create only' => ['get', 'bills.edit', ['create bills']],
    'update via view only' => ['put', 'bills.update', ['view bills']],
    'update via create only' => ['put', 'bills.update', ['create bills']],
    'add-payment via view bills' => ['post', 'bills.add-payment', ['view bills', 'create bills', 'edit bills']],
    'update-payment via create payments' => ['put', 'bills.update-payment', ['create payments', 'delete payments']],
    'remove-payment via edit payments' => ['delete', 'bills.remove-payment', ['edit payments', 'create payments']],
]);

it('forbids a wrong-verb permission on each bills route', function (string $method, string $route, array $permissions) {
    $this->actingAs(billScopeUser($permissions));

    billScopeRequest($this, $method, $route, $this->bill, $this->payment, $this->patient)->assertForbidden();
})->with('bills escalations');

// ── 2. Positive control: exactly the matching permission passes ──────────

dataset('bills positive controls', [
    'index' => ['get', 'bills.index', 'view bills'],
    'data' => ['get', 'bills.data', 'view bills'],
    'show' => ['get', 'bills.show', 'view bills'],
    'print' => ['get', 'bills.print', 'view bills'],
    'create' => ['get', 'bills.create', 'create bills'],
    'edit' => ['get', 'bills.edit', 'edit bills'],
    'add-payment' => ['post', 'bills.add-payment', 'create payments'],
    'update-payment' => ['put', 'bills.update-payment', 'edit payments'],
    'remove-payment' => ['delete', 'bills.remove-payment', 'delete payments'],
]);

it('allows exactly the matching permission on each bills route', function (string $method, string $route, string $permission) {
    $this->actingAs(billScopeUser([$permission]));

    $response = billScopeRequest($this, $method, $route, $this->bill, $this->payment, $this->patient);

    expect($response->getStatusCode())->not->toBe(403)->not->toBe(404);
})->with('bills positive controls');

it('lets create bills reach store and edit bills reach update (validation/auth passes, not 403)', function () {
    $this->actingAs(billScopeUser(['create bills']));
    $store = billScopeRequest($this, 'post', 'bills.store', $this->bill, $this->payment, $this->patient);
    expect($store->getStatusCode())->not->toBe(403)->not->toBe(404);

    $this->actingAs(billScopeUser(['edit bills']));
    $update = billScopeRequest($this, 'put', 'bills.update', $this->bill, $this->payment, $this->patient);
    expect($update->getStatusCode())->not->toBe(403)->not->toBe(404);
});

// ── 3. Side effects: a blocked write writes nothing ──────────────────────

it('creates no bill when view bills posts store', function () {
    $this->actingAs(billScopeUser(['view bills']));
    $count = Bill::count();

    billScopeRequest($this, 'post', 'bills.store', $this->bill, $this->payment, $this->patient)->assertForbidden();

    expect(Bill::count())->toBe($count);
});

it('leaves the bill unchanged when view bills puts update', function () {
    $this->actingAs(billScopeUser(['view bills']));

    billScopeRequest($this, 'put', 'bills.update', $this->bill, $this->payment, $this->patient)->assertForbidden();

    expect((float) $this->bill->fresh()->total_amount)->toBe(1000.0);
});

it('records no payment when a bills-only user posts add-payment', function () {
    $this->actingAs(billScopeUser(['view bills', 'create bills', 'edit bills']));
    $count = Payment::count();

    billScopeRequest($this, 'post', 'bills.add-payment', $this->bill, $this->payment, $this->patient)->assertForbidden();

    expect(Payment::count())->toBe($count);
});

// ── 4. Payments unchanged: delete payments still removes a payment ───────

it('still lets delete payments remove a payment line', function () {
    $this->actingAs(billScopeUser(['delete payments']));

    billScopeRequest($this, 'delete', 'bills.remove-payment', $this->bill, $this->payment, $this->patient)
        ->assertRedirect(route('bills.show', $this->bill))
        ->assertSessionHasNoErrors();

    expect(Payment::find($this->payment->id))->toBeNull();
    expect((float) $this->bill->fresh()->paid_amount)->toBe(0.0);
});

it('keeps create, edit and delete payments in the registry and seeder', function () {
    foreach (['create payments', 'edit payments', 'delete payments'] as $permission) {
        expect(PermissionRegistry::all())->toContain($permission);
        expect(file_get_contents(database_path('seeders/RolePermissionSeeder.php')))->toContain("'{$permission}'");
    }
});

// ── 5. No bill delete: route, permission and registry entry are gone ─────

it('does not register a bills destroy route', function () {
    expect(Route::has('bills.destroy'))->toBeFalse();
});

it('has no DELETE route for a bill URL', function () {
    $this->actingAs(billScopeUser(['view bills', 'create bills', 'edit bills', 'delete payments']));

    $this->delete(url('bills/'.$this->bill->id))->assertStatus(405);

    expect(Bill::find($this->bill->id))->not->toBeNull();
});

it('does not define destroy on the bill controller', function () {
    expect(method_exists(\App\Http\Controllers\BillController::class, 'destroy'))->toBeFalse();
});

it('no longer lists delete bills in the permission registry', function () {
    expect(PermissionRegistry::all())->not->toContain('delete bills');
});

it('no longer grants delete bills in the role seeder', function () {
    expect(file_get_contents(database_path('seeders/RolePermissionSeeder.php')))
        ->not->toContain('delete bills');
});

it('forbids a user with only delete bills on every bills route', function () {
    $this->actingAs(billScopeUser(['delete bills']));

    foreach ([
        ['get', 'bills.index'],
        ['get', 'bills.data'],
        ['get', 'bills.show'],
        ['get', 'bills.print'],
        ['get', 'bills.create'],
        ['post', 'bills.store'],
        ['get', 'bills.edit'],
        ['put', 'bills.update'],
    ] as [$method, $route]) {
        billScopeRequest($this, $method, $route, $this->bill, $this->payment, $this->patient)->assertForbidden();
    }
});

// ── 6. Taxes unchanged ───────────────────────────────────────────────────

it('keeps taxes on view bills or create bills', function () {
    $this->actingAs(billScopeUser(['edit bills']));
    $this->get(route('taxes.index'))->assertForbidden();

    $this->actingAs(billScopeUser(['view bills']));
    expect($this->get(route('taxes.index'))->getStatusCode())->not->toBe(403);

    $this->actingAs(billScopeUser(['create bills']));
    expect($this->get(route('taxes.index'))->getStatusCode())->not->toBe(403);
});

// ── 7. UI gating ─────────────────────────────────────────────────────────

it('shows no Delete bill control on the bills index or its row script', function () {
    $this->actingAs(billScopeUser(['view bills', 'create bills', 'edit bills']));

    $this->get(route('bills.index'))
        ->assertOk()
        ->assertDontSee('fa-trash', false)
        ->assertDontSee('name="_method" value="DELETE"', false);

    expect(file_get_contents(resource_path('js/bills-index.js')))
        ->not->toContain('fa-trash')
        ->not->toContain('value="DELETE"');
});

it('hides the create bill button from a view-only user on the index', function () {
    $this->actingAs(billScopeUser(['view bills']));

    $this->get(route('bills.index'))
        ->assertOk()
        ->assertDontSee(e(route('bills.create')), false)
        ->assertSee('data-can-edit="0"', false);
});

it('shows create bill button and edit flag to create and edit holders', function () {
    $this->actingAs(billScopeUser(['view bills', 'create bills', 'edit bills']));

    $this->get(route('bills.index'))
        ->assertOk()
        ->assertSee(e(route('bills.create')), false)
        ->assertSee('data-can-edit="1"', false);
});

it('shows payment remove control only with delete payments and no bill delete on show', function () {
    $this->actingAs(billScopeUser(['view bills', 'delete payments']));

    $removeUrl = route('bills.remove-payment', ['bill' => $this->bill->id, 'payment' => $this->payment->id]);

    $this->get(route('bills.show', $this->bill))
        ->assertOk()
        ->assertSee(e($removeUrl), false);

    $this->actingAs(billScopeUser(['view bills']));

    $this->get(route('bills.show', $this->bill))
        ->assertOk()
        ->assertDontSee(e($removeUrl), false);
});
