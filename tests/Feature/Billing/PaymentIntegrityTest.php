<?php

use App\Models\PaymentGateway;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Http;

const PF_MERCHANT = 'MERCHANT-1001';
const PF_SECRET = 'pf-secured-key-test';
const PADDLE_SECRET = 'pdl_ntfset_test_secret';

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
        'payfast.merchant_id' => PF_MERCHANT,
        'payfast.secured_key' => PF_SECRET,
    ]);
    $this->app['db']->purge('landlord');
    $this->artisan('migrate', ['--path' => 'database/migrations/landlord', '--database' => 'landlord']);
});

function integrityTenantRow(string $label = 'a'): Tenant
{
    $uid = uniqid();

    return Tenant::query()->create([
        'name' => "Integrity {$label}",
        'slug' => "integrity-{$label}-{$uid}",
        'domain' => "integrity-{$label}-{$uid}.example.test",
        'database' => "tenant_integrity_{$label}_{$uid}",
        'email' => "integrity-{$label}-{$uid}@example.com",
        'status' => 'active',
        'trial_ends_at' => now()->addDays(10),
    ]);
}

/** Bind $row as the current tenant; `$allowUpdate=false` fails the test if anything updates the tenant. */
function bindIntegrityTenant(Tenant $row, bool $allowUpdate = true): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = $row->id;
    $tenant->name = $row->name;
    $tenant->email = $row->email;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')->andReturn(true);
    if ($allowUpdate) {
        $tenant->shouldReceive('update')->andReturnTrue();
    } else {
        $tenant->shouldNotReceive('update');
    }
    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function integrityUser(): User
{
    return User::create([
        'name' => 'Integrity User',
        'email' => 'integrity-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
}

function integrityPlan(array $overrides = []): Plan
{
    return Plan::create(array_merge([
        'name' => 'Paid Plan',
        'slug' => 'paid-'.uniqid(),
        'price' => 2500,
        'billing_cycle' => 'monthly',
        'modules' => ['patients'],
        'is_active' => true,
        'sort_order' => 1,
    ], $overrides));
}

/** A pending PayFast subscription exactly as initiateCheckout() leaves it. */
function pendingPayFastSubscription(Tenant $tenant, Plan $plan): Subscription
{
    $subscription = Subscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
        'amount' => $plan->price,
        'currency' => 'PKR',
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
    ]);
    $subscription->update(['payfast_meta' => ['basket_id' => 'SUB-'.$subscription->id.'-1700000000']]);

    return $subscription->fresh();
}

/** PayFast PK callback params signed per Merchant Integration Guide v2.3 §3.2.3. */
function payfastSigned(Subscription $subscription, string $errCode = '000', ?string $secret = null): array
{
    $basket = $subscription->payfast_meta['basket_id'];

    return [
        'basket_id' => $basket,
        'err_code' => $errCode,
        'err_msg' => $errCode === '000' ? 'Approved' : 'Declined',
        'transaction_id' => 'PF-TXN-'.uniqid(),
        'validation_hash' => hash('sha256', $basket.'|'.($secret ?? PF_SECRET).'|'.PF_MERCHANT.'|'.$errCode),
    ];
}

function enableGateway(string $slug, array $credentials = []): PaymentGateway
{
    return PaymentGateway::updateOrCreate(['slug' => $slug], [
        'name' => ucfirst($slug),
        'is_enabled' => true,
        'mode' => 'sandbox',
        'credentials' => $credentials,
        'config_fields' => [],
        'sort_order' => 1,
    ]);
}

function paddleSignatureHeader(string $body, ?int $ts = null, string $secret = PADDLE_SECRET): string
{
    $ts ??= time();

    return 'ts='.$ts.';h1='.hash_hmac('sha256', $ts.':'.$body, $secret);
}

// ── S1: PayFast webhook must carry a valid PayFast validation_hash ──────────

it('rejects a forged PayFast webhook and leaves the subscription pending', function () {
    enableGateway('payfast');
    $sub = pendingPayFastSubscription(integrityTenantRow(), integrityPlan());

    $this->postJson(route('billing.payfast.webhook'), [
        'basket_id' => $sub->payfast_meta['basket_id'],
        'status' => '00',
        'err_code' => '000',
    ])->assertForbidden();

    expect($sub->fresh()->status)->toBe('pending')
        ->and(SubscriptionPayment::count())->toBe(0);
});

it('rejects a PayFast webhook signed with the wrong key', function () {
    enableGateway('payfast');
    $sub = pendingPayFastSubscription(integrityTenantRow(), integrityPlan());

    $this->postJson(route('billing.payfast.webhook'), payfastSigned($sub, '000', 'attacker-guess'))
        ->assertForbidden();

    expect($sub->fresh()->status)->toBe('pending');
});

it('activates a subscription from a correctly signed approved PayFast webhook', function () {
    enableGateway('payfast');
    $sub = pendingPayFastSubscription(integrityTenantRow(), integrityPlan());

    $this->postJson(route('billing.payfast.webhook'), payfastSigned($sub))->assertOk();

    expect($sub->fresh()->status)->toBe('active')
        ->and(SubscriptionPayment::where('subscription_id', $sub->id)->where('status', 'success')->count())->toBe(1);
});

it('rejects a signed PayFast webhook whose basket is not the one issued for that subscription', function () {
    enableGateway('payfast');
    $sub = pendingPayFastSubscription(integrityTenantRow(), integrityPlan());
    $params = payfastSigned($sub);
    $params['basket_id'] = 'SUB-'.$sub->id.'-9999999999';
    $params['validation_hash'] = hash('sha256', $params['basket_id'].'|'.PF_SECRET.'|'.PF_MERCHANT.'|000');

    $this->postJson(route('billing.payfast.webhook'), $params)->assertForbidden();

    expect($sub->fresh()->status)->toBe('pending');
});

it('does not revive a cancelled subscription from a replayed signed PayFast webhook', function () {
    enableGateway('payfast');
    $sub = pendingPayFastSubscription(integrityTenantRow(), integrityPlan());
    $sub->update(['status' => 'cancelled', 'cancelled_at' => now()]);

    $this->postJson(route('billing.payfast.webhook'), payfastSigned($sub))->assertOk();

    expect($sub->fresh()->status)->toBe('cancelled')
        ->and(SubscriptionPayment::count())->toBe(0);
});

it('returns 404 for the PayFast webhook while PayFast is disabled', function () {
    $sub = pendingPayFastSubscription(integrityTenantRow(), integrityPlan());

    $this->postJson(route('billing.payfast.webhook'), payfastSigned($sub))->assertNotFound();

    expect($sub->fresh()->status)->toBe('pending');
});

// ── S2: Paddle webhook must carry a valid Paddle-Signature ─────────────────

function paddleSubscription(Tenant $tenant, Plan $plan): Subscription
{
    return Subscription::create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'gateway' => 'paddle',
        'gateway_subscription_id' => 'sub_'.uniqid(),
        'status' => 'active',
        'amount' => $plan->price,
        'currency' => 'USD',
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
    ]);
}

function paddleCancelBody(Subscription $sub): string
{
    return json_encode(['event_type' => 'subscription.canceled', 'data' => ['id' => $sub->gateway_subscription_id]]);
}

it('rejects an unsigned Paddle webhook and leaves the subscription active', function () {
    enableGateway('paddle', ['webhook_secret' => PADDLE_SECRET]);
    $sub = paddleSubscription(integrityTenantRow(), integrityPlan());

    $this->call('POST', route('paddle.webhook'), [], [], [], ['CONTENT_TYPE' => 'application/json'], paddleCancelBody($sub))
        ->assertForbidden();

    expect($sub->fresh()->status)->toBe('active');
});

it('rejects a Paddle webhook with a wrong signature', function () {
    enableGateway('paddle', ['webhook_secret' => PADDLE_SECRET]);
    $sub = paddleSubscription(integrityTenantRow(), integrityPlan());
    $body = paddleCancelBody($sub);

    $this->call('POST', route('paddle.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_PADDLE_SIGNATURE' => paddleSignatureHeader($body, null, 'wrong-secret'),
    ], $body)->assertForbidden();

    expect($sub->fresh()->status)->toBe('active');
});

it('rejects a validly signed but stale Paddle webhook (replay window)', function () {
    enableGateway('paddle', ['webhook_secret' => PADDLE_SECRET]);
    $sub = paddleSubscription(integrityTenantRow(), integrityPlan());
    $body = paddleCancelBody($sub);

    $this->call('POST', route('paddle.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_PADDLE_SIGNATURE' => paddleSignatureHeader($body, time() - 3600),
    ], $body)->assertForbidden();

    expect($sub->fresh()->status)->toBe('active');
});

it('rejects Paddle webhooks when no webhook secret is configured', function () {
    enableGateway('paddle', []);
    $sub = paddleSubscription(integrityTenantRow(), integrityPlan());
    $body = paddleCancelBody($sub);

    $this->call('POST', route('paddle.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_PADDLE_SIGNATURE' => paddleSignatureHeader($body),
    ], $body)->assertForbidden();

    expect($sub->fresh()->status)->toBe('active');
});

it('processes a correctly signed Paddle webhook', function () {
    enableGateway('paddle', ['webhook_secret' => PADDLE_SECRET]);
    $sub = paddleSubscription(integrityTenantRow(), integrityPlan());
    $body = paddleCancelBody($sub);

    $this->call('POST', route('paddle.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_PADDLE_SIGNATURE' => paddleSignatureHeader($body),
    ], $body)->assertOk();

    expect($sub->fresh()->status)->toBe('cancelled');
});

it('returns 404 for the Paddle webhook while Paddle is disabled', function () {
    $sub = paddleSubscription(integrityTenantRow(), integrityPlan());
    $body = paddleCancelBody($sub);

    $this->call('POST', route('paddle.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_PADDLE_SIGNATURE' => paddleSignatureHeader($body),
    ], $body)->assertNotFound();

    expect($sub->fresh()->status)->toBe('active');
});

// ── S3: PayFast success/cancel returns are verified and tenant-scoped ──────

it('does not activate from a PayFast success return without a valid validation_hash', function () {
    $row = integrityTenantRow();
    bindIntegrityTenant($row);
    $this->actingAs(integrityUser());
    $sub = pendingPayFastSubscription($row, integrityPlan());

    $this->get(route('billing.payfast.success', ['subscription_id' => $sub->id]))
        ->assertRedirect(route('billing.index'));

    expect($sub->fresh()->status)->toBe('pending')
        ->and(SubscriptionPayment::count())->toBe(0);
});

it('does not activate another tenant\'s subscription from a PayFast success return', function () {
    $mine = integrityTenantRow('mine');
    $other = integrityTenantRow('other');
    bindIntegrityTenant($mine);
    $this->actingAs(integrityUser());
    $theirs = pendingPayFastSubscription($other, integrityPlan());

    $this->get(route('billing.payfast.success', ['subscription_id' => $theirs->id] + payfastSigned($theirs)))
        ->assertRedirect(route('billing.index'));

    expect($theirs->fresh()->status)->toBe('pending')
        ->and(SubscriptionPayment::count())->toBe(0);
});

it('activates the tenant\'s own subscription from a verified PayFast success return', function () {
    $row = integrityTenantRow();
    bindIntegrityTenant($row);
    $this->actingAs(integrityUser());
    $sub = pendingPayFastSubscription($row, integrityPlan());

    $this->get(route('billing.payfast.success', ['subscription_id' => $sub->id] + payfastSigned($sub)))
        ->assertRedirect(route('billing.index'));

    expect($sub->fresh()->status)->toBe('active');
});

it('does not activate from a verified PayFast success return whose err_code is a decline', function () {
    $row = integrityTenantRow();
    bindIntegrityTenant($row);
    $this->actingAs(integrityUser());
    $sub = pendingPayFastSubscription($row, integrityPlan());

    $this->get(route('billing.payfast.success', ['subscription_id' => $sub->id] + payfastSigned($sub, '002')));

    expect($sub->fresh()->status)->not->toBe('active');
});

it('does not cancel another tenant\'s subscription from a PayFast cancel return', function () {
    $mine = integrityTenantRow('mine');
    $other = integrityTenantRow('other');
    bindIntegrityTenant($mine);
    $this->actingAs(integrityUser());
    $theirs = pendingPayFastSubscription($other, integrityPlan());

    $this->get(route('billing.payfast.cancel', ['subscription_id' => $theirs->id]))
        ->assertRedirect(route('billing.index'));

    expect($theirs->fresh()->status)->toBe('pending');
});

// ── S4: Paddle activate requires a verified, paid, own-tenant transaction ──

function paddleTransaction(Tenant $tenant, Plan $plan, string $status = 'completed'): array
{
    return [
        'data' => [
            'id' => 'txn_'.uniqid(),
            'status' => $status,
            'subscription_id' => 'sub_'.uniqid(),
            'customer_id' => 'ctm_1',
            'currency_code' => 'USD',
            'custom_data' => ['tenant_id' => (string) $tenant->id],
            'items' => [['price' => ['id' => $plan->paddle_price_id]]],
        ],
    ];
}

it('does not activate or clear the trial when Paddle cannot verify the transaction', function () {
    $row = integrityTenantRow();
    bindIntegrityTenant($row, allowUpdate: false);
    enableGateway('paddle', ['api_key' => 'pdl_key', 'webhook_secret' => PADDLE_SECRET]);
    $this->actingAs(integrityUser());
    Http::fake(['*paddle.com/transactions/*' => Http::response(['error' => 'not found'], 404)]);

    $this->postJson(route('subscription.activate'), ['transaction_id' => 'txn_forged'])
        ->assertStatus(422);

    expect(Subscription::where('status', 'active')->count())->toBe(0)
        ->and(SubscriptionPayment::count())->toBe(0);
});

it('does not activate an unpaid Paddle transaction', function () {
    $row = integrityTenantRow();
    bindIntegrityTenant($row, allowUpdate: false);
    $plan = integrityPlan(['paddle_price_id' => 'pri_paid']);
    enableGateway('paddle', ['api_key' => 'pdl_key', 'webhook_secret' => PADDLE_SECRET]);
    $this->actingAs(integrityUser());
    $txn = paddleTransaction($row, $plan, 'ready');
    Http::fake(['*paddle.com/transactions/*' => Http::response($txn, 200)]);

    $this->postJson(route('subscription.activate'), ['transaction_id' => $txn['data']['id']])
        ->assertStatus(422);

    expect(Subscription::where('status', 'active')->count())->toBe(0);
});

it('does not activate a Paddle transaction that belongs to another tenant', function () {
    $mine = integrityTenantRow('mine');
    $other = integrityTenantRow('other');
    bindIntegrityTenant($mine, allowUpdate: false);
    $plan = integrityPlan(['paddle_price_id' => 'pri_paid']);
    enableGateway('paddle', ['api_key' => 'pdl_key', 'webhook_secret' => PADDLE_SECRET]);
    $this->actingAs(integrityUser());
    $txn = paddleTransaction($other, $plan);
    Http::fake(['*paddle.com/transactions/*' => Http::response($txn, 200)]);

    $this->postJson(route('subscription.activate'), ['transaction_id' => $txn['data']['id']])
        ->assertStatus(422);

    expect(Subscription::where('status', 'active')->count())->toBe(0);
});

it('activates a verified, paid Paddle transaction for this tenant', function () {
    $row = integrityTenantRow();
    bindIntegrityTenant($row);
    $plan = integrityPlan(['paddle_price_id' => 'pri_paid']);
    enableGateway('paddle', ['api_key' => 'pdl_key', 'webhook_secret' => PADDLE_SECRET]);
    $this->actingAs(integrityUser());
    $txn = paddleTransaction($row, $plan);
    Http::fake(['*paddle.com/transactions/*' => Http::response($txn, 200)]);

    $this->postJson(route('subscription.activate'), ['transaction_id' => $txn['data']['id']])
        ->assertOk();

    expect(Subscription::where('tenant_id', $row->id)->where('status', 'active')->where('plan_id', $plan->id)->count())->toBe(1);
});
