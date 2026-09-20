<?php

use App\Models\Patient;
use App\Models\Tenant;
use App\Models\TestOrder;
use App\Models\User;
use App\Models\Visit;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
});

function bindTenantWithModulesForTestOrders(array $modules): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, $modules, true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function testOrderModuleUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Test Order Module User',
        'email' => 'tom-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

function makeVisitOwnedTestOrder(string $visitType): TestOrder
{
    $patient = Patient::create([
        'name' => 'TO Patient '.$visitType,
        'gender' => 'male',
        'age' => 40,
        'phone' => '0300'.random_int(1000000, 9999999),
    ]);

    $visit = Visit::create([
        'patient_id' => $patient->id,
        'visit_type' => $visitType,
        'visit_datetime' => now(),
        'status' => 'registered',
        'doctor_id' => null,
    ]);

    return TestOrder::create([
        'visit_id' => $visit->id,
        'test_name' => 'CBC',
        'quantity' => 1,
        'priority' => 'routine',
        'status' => 'ordered',
        'ordered_at' => now(),
    ]);
}

it('allows test-orders.result for an IPD-owned order when tenant has ipd only', function () {
    bindTenantWithModulesForTestOrders(['ipd']);
    $this->actingAs(testOrderModuleUser(['edit visits']));
    $order = makeVisitOwnedTestOrder('ipd');

    $this->post(route('test-orders.result', $order), ['results' => 'Normal'])
        ->assertRedirect();
});

it('allows test-orders.remove for an IPD-owned order when tenant has ipd only', function () {
    bindTenantWithModulesForTestOrders(['ipd']);
    $this->actingAs(testOrderModuleUser(['edit visits']));
    $order = makeVisitOwnedTestOrder('ipd');

    $this->delete(route('test-orders.remove', $order))
        ->assertRedirect();
});

it('forbids test-orders.result for an IPD-owned order when tenant has visits but not ipd', function () {
    bindTenantWithModulesForTestOrders(['visits']);
    $this->actingAs(testOrderModuleUser(['edit visits']));
    $order = makeVisitOwnedTestOrder('ipd');

    $this->post(route('test-orders.result', $order), ['results' => 'Normal'])
        ->assertForbidden();
});

it('allows test-orders.result for an OPD-owned order when tenant has visits only', function () {
    bindTenantWithModulesForTestOrders(['visits']);
    $this->actingAs(testOrderModuleUser(['edit visits']));
    $order = makeVisitOwnedTestOrder('opd');

    $this->post(route('test-orders.result', $order), ['results' => 'Normal'])
        ->assertRedirect();
});

it('forbids test-orders.result for an OPD-owned order when tenant lacks visits', function () {
    bindTenantWithModulesForTestOrders(['ipd']);
    $this->actingAs(testOrderModuleUser(['edit visits']));
    $order = makeVisitOwnedTestOrder('opd');

    $this->post(route('test-orders.result', $order), ['results' => 'Normal'])
        ->assertForbidden();
});

it('allows test-orders.result for an emergency-owned order when tenant has emergency only', function () {
    bindTenantWithModulesForTestOrders(['emergency']);
    $this->actingAs(testOrderModuleUser(['edit visits']));
    $order = makeVisitOwnedTestOrder('emergency');

    $this->post(route('test-orders.result', $order), ['results' => 'Normal'])
        ->assertRedirect();
});

it('forbids orphaned test orders closed at CheckModule', function () {
    bindTenantWithModulesForTestOrders(['visits', 'ipd', 'emergency']);
    $this->actingAs(testOrderModuleUser(['edit visits']));

    $order = makeVisitOwnedTestOrder('opd');

    // SQLite cannot toggle foreign_keys inside RefreshDatabase transactions, so
    // simulate an orphan by binding a TestOrder whose visit relation is null.
    \Illuminate\Support\Facades\Route::bind('testOrder', function ($value) {
        $bound = TestOrder::query()->findOrFail($value);
        $bound->setRelation('visit', null);

        return $bound;
    });

    $this->post(route('test-orders.result', $order), ['results' => 'Normal'])
        ->assertForbidden();
});
