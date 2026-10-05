<?php

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
    $this->artisan('migrate', ['--path' => 'database/migrations/landlord', '--database' => 'landlord']);
});

/** Create a landlord tenant row and bind a permissive partial mock of it as the current tenant. */
function bindMspTenant(bool $onTrial = false, ?Plan $plan = null): Tenant
{
    $uid = uniqid();
    $row = Tenant::query()->create([
        'name' => 'MSP Hospital',
        'slug' => 'msp-'.$uid,
        'domain' => 'msp-'.$uid.'.example.test',
        'database' => 'tenant_msp_'.$uid,
        'email' => 'msp-'.$uid.'@example.com',
        'status' => 'active',
        'trial_ends_at' => $onTrial ? now()->addDays(10) : null,
    ]);

    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = $row->id;
    $tenant->name = $row->name;
    $tenant->email = $row->email;
    $tenant->status = 'active';
    $tenant->trial_ends_at = $row->trial_ends_at;
    $tenant->setRelation('plan', $plan);
    $tenant->setRelation('activeSubscription', null);
    $tenant->shouldReceive('hasModule')->andReturn(true);
    $tenant->shouldReceive('update')->andReturnTrue();

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function mspUser(array $permissions = [], ?string $role = null): User
{
    $user = User::create([
        'name' => 'MSP User',
        'email' => 'msp-user-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    if ($permissions !== []) {
        $user->givePermissionTo($permissions);
    }

    if ($role !== null) {
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);
    }

    return $user;
}

function mspPlan(array $overrides = []): Plan
{
    return Plan::create(array_merge([
        'name' => 'MSP Plan',
        'slug' => 'msp-plan-'.uniqid(),
        'price' => 1000,
        'billing_cycle' => 'monthly',
        'modules' => ['patients'],
        'is_active' => true,
        'sort_order' => 1,
    ], $overrides));
}

/**
 * BillingController::subscribe validates `exists:plans,id` on the default connection, which is
 * `landlord` in the app (.env) but `tenant` under phpunit.xml. Mirror the plan id there so
 * validation passes and the request reaches the controller as it does in production.
 */
function mspMirrorPlanForValidation(Plan $plan): void
{
    $schema = Schema::connection('tenant');
    if (! $schema->hasTable('plans')) {
        $schema->create('plans', fn (Blueprint $table) => $table->id());
    }
    DB::connection('tenant')->table('plans')->insert(['id' => $plan->id]);
}

function mspPendingSubscription(Tenant $tenant, Plan $plan): Subscription
{
    return Subscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
        'amount' => $plan->price,
        'currency' => 'PKR',
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
    ]);
}

// ── Denied: logged-in user without `manage subscription` ─────────────────────

it('forbids billing.index without manage subscription', function () {
    bindMspTenant();
    $this->actingAs(mspUser());

    $this->get(route('billing.index'))->assertForbidden();
});

it('forbids billing.subscribe without manage subscription', function () {
    bindMspTenant();
    $plan = mspPlan(['price' => 0]);
    mspMirrorPlanForValidation($plan);
    $this->actingAs(mspUser());

    $this->post(route('billing.subscribe'), ['plan_id' => $plan->id])->assertForbidden();
    expect(Subscription::count())->toBe(0);
});

it('forbids billing.payfast.success without manage subscription', function () {
    $tenant = bindMspTenant();
    $sub = mspPendingSubscription($tenant, mspPlan());
    $this->actingAs(mspUser());

    $this->get(route('billing.payfast.success', ['subscription_id' => $sub->id]))->assertForbidden();
});

it('forbids billing.payfast.cancel without manage subscription', function () {
    $tenant = bindMspTenant();
    $sub = mspPendingSubscription($tenant, mspPlan());
    $this->actingAs(mspUser());

    $this->get(route('billing.payfast.cancel', ['subscription_id' => $sub->id]))->assertForbidden();
    expect($sub->fresh()->status)->toBe('pending');
});

it('forbids subscription.index without manage subscription', function () {
    bindMspTenant();
    $this->actingAs(mspUser());

    $this->get(route('subscription.index'))->assertForbidden();
});

it('forbids subscription.activate without manage subscription', function () {
    bindMspTenant();
    $this->actingAs(mspUser());

    $this->postJson(route('subscription.activate'), ['transaction_id' => 'txn_x'])->assertForbidden();
});

it('forbids billing pages for a user whose role is named Hospital Administrator but lacks the permission', function () {
    bindMspTenant();
    $this->actingAs(mspUser([], 'Hospital Administrator'));

    $this->get(route('billing.index'))->assertForbidden();
    $this->get(route('subscription.index'))->assertForbidden();
});

// ── Allowed: `manage subscription` holder ────────────────────────────────────

it('allows billing.index with manage subscription', function () {
    bindMspTenant();
    mspPlan();
    $this->actingAs(mspUser(['manage subscription']));

    $this->get(route('billing.index'))->assertOk();
});

it('allows subscription.index with manage subscription', function () {
    bindMspTenant();
    mspPlan();
    $this->actingAs(mspUser(['manage subscription']));

    $this->get(route('subscription.index'))->assertOk();
});

it('lets a manage subscription holder switch to a free plan', function () {
    $tenant = bindMspTenant();
    $plan = mspPlan(['price' => 0, 'name' => 'Free']);
    mspMirrorPlanForValidation($plan);
    $this->actingAs(mspUser(['manage subscription']));

    $this->post(route('billing.subscribe'), ['plan_id' => $plan->id])
        ->assertRedirect(route('billing.index'));

    expect(Subscription::where('tenant_id', $tenant->id)->where('status', 'active')->count())->toBe(1);
});

// ── Sidebar subscription link ────────────────────────────────────────────────

it('shows the sidebar subscription link to a manage subscription holder', function () {
    bindMspTenant(plan: mspPlan());
    $this->actingAs(mspUser(['manage subscription']));

    expect(view('partials.sidebar')->render())->toContain(route('subscription.index'));
});

it('shows the sidebar upgrade link to a manage subscription holder when no plan is set', function () {
    bindMspTenant();
    $this->actingAs(mspUser(['manage subscription']));

    expect(view('partials.sidebar')->render())->toContain(route('subscription.index'));
});

it('hides the sidebar subscription link from a role named Hospital Administrator without the permission', function () {
    bindMspTenant(plan: mspPlan());
    $this->actingAs(mspUser([], 'Hospital Administrator'));

    expect(view('partials.sidebar')->render())->not->toContain(route('subscription.index'));
});

// ── Trial banner "Upgrade Now" ───────────────────────────────────────────────

it('shows the trial banner Upgrade Now link to a manage subscription holder', function () {
    bindMspTenant(onTrial: true);
    $this->actingAs(mspUser(['manage subscription']));

    $html = view('admin.layout', ['errors' => new ViewErrorBag])->render();

    expect($html)->toContain('Trial ends in')
        ->and($html)->toContain('Upgrade Now');
});

it('hides the trial banner Upgrade Now link without manage subscription', function () {
    bindMspTenant(onTrial: true);
    $this->actingAs(mspUser([], 'Hospital Administrator'));

    $html = view('admin.layout', ['errors' => new ViewErrorBag])->render();

    expect($html)->toContain('Trial ends in')
        ->and($html)->not->toContain('Upgrade Now')
        ->and($html)->not->toContain(route('subscription.index'));
});
