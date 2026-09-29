<?php

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BillingService;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);
    $this->withoutVite();

    config([
        'database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);
    $this->app['db']->purge('landlord');
    $this->artisan('migrate', [
        '--path' => 'database/migrations/landlord',
        '--database' => 'landlord',
    ]);
});

function bindBillingTenant(): Tenant
{
    $uid = uniqid();
    $row = Tenant::query()->create([
        'name' => 'Billing Test Hospital',
        'slug' => 'billing-test-'.$uid,
        'domain' => 'billing-test-'.$uid.'.example.test',
        'database' => 'tenant_billing_test_'.$uid,
        'email' => 'billing-'.$uid.'@example.com',
        'status' => 'active',
    ]);

    // Re-bind as partial mock so hasModule stays permissive in HTTP tests
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = $row->id;
    $tenant->name = $row->name;
    $tenant->email = $row->email;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')->andReturn(true);
    $tenant->shouldReceive('update')->andReturnTrue();

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function billingUser(): User
{
    return User::create([
        'name' => 'Billing User',
        'email' => 'billing-user-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
}

function makeBillingPlan(array $overrides = []): Plan
{
    return Plan::create(array_merge([
        'name' => 'Test Plan',
        'slug' => 'test-plan-'.uniqid(),
        'price' => 1000,
        'billing_cycle' => 'monthly',
        'modules' => ['patients'],
        'is_active' => true,
        'sort_order' => 1,
    ], $overrides));
}

it('resolves BillingService without constructing PayFast eagerly', function () {
    $service = app(BillingService::class);

    expect($service)->toBeInstanceOf(BillingService::class);
});

it('loads billing index without requiring PayFast configuration', function () {
    bindBillingTenant();
    makeBillingPlan();
    $this->actingAs(billingUser());

    $response = $this->get(route('billing.index'));

    expect($response->status())->not->toBe(500);
    $response->assertSuccessful();
});

it('loads payfast success callback without constructing PayFast', function () {
    $tenant = bindBillingTenant();
    $this->actingAs(billingUser());
    $plan = makeBillingPlan();

    $subscription = Subscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
        'amount' => 1000,
        'currency' => 'PKR',
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
    ]);

    $response = $this->get(route('billing.payfast.success', [
        'subscription_id' => $subscription->id,
    ]));

    expect($response->status())->not->toBe(500);
    $response->assertRedirect(route('billing.index'));
});

it('loads payfast cancel callback without constructing PayFast', function () {
    $tenant = bindBillingTenant();
    $this->actingAs(billingUser());
    $plan = makeBillingPlan(['name' => 'Cancel Plan', 'slug' => 'cancel-plan-'.uniqid(), 'price' => 500]);

    $subscription = Subscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
        'amount' => 500,
        'currency' => 'PKR',
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
    ]);

    $response = $this->get(route('billing.payfast.cancel', [
        'subscription_id' => $subscription->id,
    ]));

    expect($response->status())->not->toBe(500);
    $response->assertRedirect(route('billing.index'));
});

it('still resolves a PayFast client lazily when checkout is initiated', function () {
    $service = app(BillingService::class);

    $ref = new ReflectionClass($service);
    $prop = $ref->getProperty('payfast');
    $prop->setAccessible(true);
    expect($prop->getValue($service))->toBeNull();

    $tenant = bindBillingTenant();
    $plan = makeBillingPlan(['name' => 'Paid Plan', 'slug' => 'paid-plan-'.uniqid(), 'price' => 2500]);

    $result = $service->initiateCheckout($tenant, $plan);

    expect($result)->toHaveKey('success')
        ->and($result['success'])->toBeFalse()
        ->and($result['error'] ?? '')->not->toContain('Too few arguments');
});
