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

// ── DS-2: expired trial (EnsureTenantActive enforced) ────────────────────────

/** Expire the bound tenant's trial; `activeSubscription` stays null unless one is given. */
function mspExpireTrial(Tenant $tenant, ?Subscription $activeSubscription = null): Tenant
{
    $tenant->trial_ends_at = now()->subDays(3);
    $tenant->setRelation('activeSubscription', $activeSubscription);

    return $tenant;
}

/** GET $url and follow at most $maxHops redirects; returns [final response, hops taken]. */
function mspFollow($test, string $url, int $maxHops = 2): array
{
    $response = $test->get($url);
    $hops = 0;

    while ($response->isRedirect() && $hops < $maxHops) {
        $response = $test->get($response->headers->get('Location'));
        $hops++;
    }

    return [$response, $hops];
}

describe('expired trial', function () {
    beforeEach(function () {
        // The file-level beforeEach bypasses EnsureTenantActive; these tests exercise it.
        $this->withMiddleware(\App\Http\Middleware\EnsureTenantActive::class);
    });

    // Non-admin (no `manage subscription`)

    it('shows the 402 trial-expired page on the dashboard to a user without manage subscription', function () {
        mspExpireTrial(bindMspTenant());
        $this->actingAs(mspUser([], 'Hospital Administrator'));

        $response = $this->get(route('dashboard'));

        $response->assertStatus(402)
            ->assertViewIs('errors.trial-expired')
            ->assertSee('ask your administrator')
            ->assertDontSee('Subscribe Now')
            ->assertDontSee(route('subscription.index'));
    });

    it('shows the 402 page, not a redirect or 403, on subscription.index without manage subscription', function () {
        mspExpireTrial(bindMspTenant());
        $this->actingAs(mspUser());

        $this->get(route('subscription.index'))
            ->assertStatus(402)
            ->assertViewIs('errors.trial-expired');
    });

    it('still lets a user without manage subscription log out', function () {
        mspExpireTrial(bindMspTenant());
        $this->actingAs(mspUser());

        $this->post(route('logout'))->assertRedirect();
        $this->assertGuest();
    });

    // Admin (`manage subscription`)

    it('redirects a manage subscription holder from the dashboard to subscription.index', function () {
        mspExpireTrial(bindMspTenant());
        mspPlan();
        $this->actingAs(mspUser(['manage subscription']));

        $this->get(route('dashboard'))->assertRedirect(route('subscription.index'));
    });

    it('lets a manage subscription holder open subscription.index', function () {
        mspExpireTrial(bindMspTenant());
        mspPlan();
        $this->actingAs(mspUser(['manage subscription']));

        $this->get(route('subscription.index'))->assertOk();
    });

    it('shows Subscribe Now on the trial-expired page to a manage subscription holder', function () {
        $tenant = mspExpireTrial(bindMspTenant());
        $this->actingAs(mspUser(['manage subscription']));

        $html = view('errors.trial-expired', ['tenant' => $tenant])->render();

        expect($html)->toContain('Subscribe Now')
            ->and($html)->toContain(route('subscription.index'))
            ->and($html)->not->toContain('ask your administrator');
    });

    // No redirect loop

    it('settles within two hops from the dashboard for each user type', function (array $permissions, int $expectedStatus) {
        mspExpireTrial(bindMspTenant());
        mspPlan();
        $this->actingAs(mspUser($permissions));

        [$response, $hops] = mspFollow($this, route('dashboard'));

        expect($response->isRedirect())->toBeFalse()
            ->and($hops)->toBeLessThanOrEqual(2)
            ->and($response->getStatusCode())->toBe($expectedStatus);
    })->with([
        'without manage subscription' => [[], 402],
        'with manage subscription' => [['manage subscription'], 200],
    ]);

    // Guests

    it('sends a guest on a protected route to login, not to subscription or a loop', function () {
        mspExpireTrial(bindMspTenant());

        // `auth` has middleware priority over the `tenant` group, so it runs first for guests.
        $this->get(route('dashboard'))->assertRedirect(route('login'));

        [$response, $hops] = mspFollow($this, route('dashboard'), 1);

        expect($hops)->toBe(1)
            ->and($response->headers->get('Location'))->toBe(config('app.url').'/signin');
    });

    it('keeps the login route reachable for a guest', function () {
        mspExpireTrial(bindMspTenant());

        // The tenant login action hands off to central sign-in by design; reaching it
        // (instead of the 402 page or a subscription redirect) proves login is not blocked.
        $this->get(route('login'))->assertRedirect(config('app.url').'/signin');
    });

    // Regression: tenants that are not blocked

    it('does not block a tenant still on trial', function (array $permissions) {
        bindMspTenant(onTrial: true);
        $this->actingAs(mspUser($permissions));

        $this->get(route('dashboard'))->assertOk();
    })->with([
        'without manage subscription' => [[]],
        'with manage subscription' => [['manage subscription']],
    ]);

    it('does not block an expired-trial tenant that has an active subscription', function (array $permissions) {
        $tenant = bindMspTenant();
        $plan = mspPlan();
        mspExpireTrial($tenant, new Subscription([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]));
        $this->actingAs(mspUser($permissions));

        $this->get(route('dashboard'))->assertOk();
    })->with([
        'without manage subscription' => [[]],
        'with manage subscription' => [['manage subscription']],
    ]);
});
