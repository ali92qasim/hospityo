<?php

namespace App\Http\Controllers;

use App\Models\PaymentGateway;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\Payments\PaddleWebhookSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function index()
    {
        $tenant = Tenant::current();
        $plans = Plan::active()->orderBy('sort_order')->get();
        $currentSubscription = $tenant->activeSubscription;
        $gateway = PaymentGateway::enabled()->orderBy('sort_order')->first();

        // Payment history for the tenant
        $paymentHistory = \App\Models\SubscriptionPayment::where('tenant_id', $tenant->id)
            ->orderBy('paid_at', 'desc')
            ->limit(20)
            ->get();

        return view('admin.subscription.index', compact('tenant', 'plans', 'currentSubscription', 'gateway', 'paymentHistory'));
    }

    /**
     * Activate subscription after successful Paddle checkout.
     * Called via AJAX from the client after checkout.completed event.
     */
    public function activate(Request $request)
    {
        try {
            $tenant = Tenant::current();
            $gateway = PaymentGateway::enabled()->where('slug', 'paddle')->first();

            if (!$tenant || !$gateway) {
                return response()->json(['success' => false, 'message' => 'Invalid request'], 400);
            }

            $transactionId = $request->input('transaction_id');

            if (!$transactionId) {
                return response()->json(['success' => false, 'message' => 'Missing transaction ID'], 400);
            }

            // The transaction must be confirmed with Paddle itself — paid, for this tenant,
            // for a known plan. Anything less leaves the subscription and trial untouched.
            $transactionData = $this->fetchPaddleTransaction($gateway, (string) $transactionId);
            $failure = $this->paddleTransactionProblem($transactionData, $tenant, $request->input('subscription_id'));

            if ($failure !== null) {
                Log::warning('[Subscription] Paddle activation rejected', [
                    'tenant_id' => $tenant->id,
                    'transaction_id' => $transactionId,
                    'reason' => $failure,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'We could not verify this payment with Paddle, so your subscription was not changed.',
                ], 422);
            }

            $plan = Plan::where('paddle_price_id', $transactionData['items'][0]['price']['id'])->first();
            $subId = $transactionData['subscription_id'] ?? null;
            $customerId = $transactionData['customer_id'] ?? null;

            // Create or update subscription
            $subscription = Subscription::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'gateway' => 'paddle',
                    'gateway_subscription_id' => $subId,
                ],
                [
                    'plan_id' => $plan?->id,
                    'gateway_customer_id' => $customerId,
                    'status' => 'active',
                    'amount' => $plan?->price ?? 0,
                    'currency' => $transactionData['currency_code'] ?? 'USD',
                    'starts_at' => now(),
                    'ends_at' => now()->addMonth(),
                    'trial_ends_at' => null,
                ]
            );

            // Update tenant — clear trial, set plan
            $tenant->update([
                'plan_id' => $plan?->id,
                'trial_ends_at' => null,
            ]);

            // Record subscription payment for history (once per Paddle transaction)
            $subscription->payments()->firstOrCreate(['gateway_transaction_id' => $transactionId], [
                'tenant_id'              => $tenant->id,
                'amount'                 => $plan?->price ?? 0,
                'currency'               => $transactionData['currency_code'] ?? 'USD',
                'payment_method'         => 'paddle',
                'status'                 => 'completed',
                'paid_at'                => now(),
                'gateway_transaction_id' => $transactionId,
            ]);

            Log::info('[Subscription] Activated via checkout callback', [
                'tenant_id' => $tenant->id,
                'plan' => $plan?->name,
                'transaction_id' => $transactionId,
                'subscription_id' => $subId,
            ]);

            return response()->json(['success' => true, 'message' => 'Subscription activated']);
        } catch (\Throwable $e) {
            Log::error('[Subscription] Activation failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Activation failed'], 500);
        }
    }

    /**
     * Handle Paddle webhook notifications.
     * This route is excluded from CSRF and tenant middleware.
     */
    public function paddleWebhook(Request $request)
    {
        // Route middleware already 404s while Paddle is disabled.
        $gateway = PaymentGateway::where('slug', 'paddle')->firstOrFail();

        if (! PaddleWebhookSignature::isValid(
            $request->getContent(),
            $request->header('Paddle-Signature'),
            $gateway->getCredential('webhook_secret'),
        )) {
            Log::warning('[Paddle Webhook] Rejected: missing or invalid Paddle-Signature');

            return response('Invalid signature', 403);
        }

        $payload = $request->all();
        $eventType = $payload['event_type'] ?? null;

        Log::info('[Paddle Webhook] Received', ['event' => $eventType, 'data' => $payload]);

        try {
            match ($eventType) {
                'subscription.created' => $this->handleSubscriptionCreated($payload['data']),
                'subscription.updated' => $this->handleSubscriptionUpdated($payload['data']),
                'subscription.canceled' => $this->handleSubscriptionCanceled($payload['data']),
                'subscription.activated' => $this->handleSubscriptionActivated($payload['data']),
                'transaction.completed' => $this->handleTransactionCompleted($payload['data']),
                default => Log::info("[Paddle Webhook] Unhandled event: {$eventType}"),
            };
        } catch (\Throwable $e) {
            Log::error('[Paddle Webhook] Error processing', ['error' => $e->getMessage()]);
            return response('Error', 500);
        }

        return response('OK', 200);
    }

    protected function handleSubscriptionCreated(array $data): void
    {
        $customData = $data['custom_data'] ?? [];
        $tenantId = $customData['tenant_id'] ?? null;

        if (!$tenantId) {
            Log::warning('[Paddle] subscription.created missing tenant_id in custom_data');
            return;
        }

        $tenant = Tenant::find($tenantId);
        if (!$tenant) return;

        // Find plan by paddle price ID
        $priceId = $data['items'][0]['price']['id'] ?? null;
        $plan = $priceId ? Plan::where('paddle_price_id', $priceId)->first() : null;

        Subscription::updateOrCreate(
            ['gateway_subscription_id' => $data['id']],
            [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan?->id,
                'gateway' => 'paddle',
                'gateway_customer_id' => $data['customer_id'] ?? null,
                'status' => $this->mapPaddleStatus($data['status']),
                'amount' => $data['items'][0]['price']['unit_price']['amount'] ?? 0,
                'currency' => $data['currency_code'] ?? 'USD',
                'starts_at' => $data['started_at'] ?? now(),
                'ends_at' => $data['current_billing_period']['ends_at'] ?? null,
            ]
        );

        // Update tenant plan
        if ($plan) {
            $tenant->update(['plan_id' => $plan->id]);
        }
    }

    protected function handleSubscriptionUpdated(array $data): void
    {
        $sub = Subscription::where('gateway_subscription_id', $data['id'])->first();
        if (!$sub) return;

        $priceId = $data['items'][0]['price']['id'] ?? null;
        $plan = $priceId ? Plan::where('paddle_price_id', $priceId)->first() : null;

        $sub->update([
            'status' => $this->mapPaddleStatus($data['status']),
            'plan_id' => $plan?->id ?? $sub->plan_id,
            'ends_at' => $data['current_billing_period']['ends_at'] ?? $sub->ends_at,
        ]);

        // Update tenant plan if changed
        if ($plan && $sub->tenant) {
            $sub->tenant->update(['plan_id' => $plan->id]);
        }
    }

    protected function handleSubscriptionCanceled(array $data): void
    {
        $sub = Subscription::where('gateway_subscription_id', $data['id'])->first();
        if (!$sub) return;

        $sub->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    protected function handleSubscriptionActivated(array $data): void
    {
        $sub = Subscription::where('gateway_subscription_id', $data['id'])->first();
        if (!$sub) return;

        $sub->update(['status' => 'active']);
    }

    protected function handleTransactionCompleted(array $data): void
    {
        $subscriptionId = $data['subscription_id'] ?? null;
        if (!$subscriptionId) return;

        $sub = Subscription::where('gateway_subscription_id', $subscriptionId)->first();
        if (!$sub) return;

        $sub->payments()->create([
            'amount' => ($data['details']['totals']['total'] ?? 0) / 100,
            'currency' => $data['currency_code'] ?? 'USD',
            'payment_method' => 'paddle',
            'status' => 'completed',
            'paid_at' => now(),
            'gateway_transaction_id' => $data['id'] ?? null,
        ]);
    }

    /**
     * Fetch a transaction from the Paddle API; null when it can't be confirmed.
     *
     * @return array<string, mixed>|null
     */
    protected function fetchPaddleTransaction(PaymentGateway $gateway, string $transactionId): ?array
    {
        $apiKey = $gateway->getCredential('api_key');
        if ($apiKey === '') {
            return null;
        }

        $baseUrl = $gateway->isSandbox() ? 'https://sandbox-api.paddle.com' : 'https://api.paddle.com';

        try {
            $response = Http::withToken($apiKey)->get("{$baseUrl}/transactions/".rawurlencode($transactionId));

            return $response->successful() ? $response->json('data') : null;
        } catch (\Throwable $e) {
            Log::warning('[Subscription] Paddle API call failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Why a Paddle transaction may not activate this tenant's subscription, or null if it may.
     *
     * @param  array<string, mixed>|null  $transaction
     */
    protected function paddleTransactionProblem(?array $transaction, Tenant $tenant, mixed $claimedSubscriptionId): ?string
    {
        if ($transaction === null) {
            return 'transaction could not be retrieved from Paddle';
        }

        if (! in_array($transaction['status'] ?? null, ['paid', 'completed'], true)) {
            return 'transaction is not paid';
        }

        if ((string) ($transaction['custom_data']['tenant_id'] ?? '') !== (string) $tenant->id) {
            return 'transaction belongs to another tenant';
        }

        $priceId = $transaction['items'][0]['price']['id'] ?? null;
        if (! $priceId || ! Plan::where('paddle_price_id', $priceId)->exists()) {
            return 'transaction is not for a known plan';
        }

        if ($claimedSubscriptionId !== null && (string) $claimedSubscriptionId !== (string) ($transaction['subscription_id'] ?? '')) {
            return 'subscription id does not match the transaction';
        }

        return null;
    }

    protected function mapPaddleStatus(string $status): string
    {
        return match ($status) {
            'active' => 'active',
            'trialing' => 'trialing',
            'past_due' => 'past_due',
            'paused' => 'paused',
            'canceled' => 'cancelled',
            default => $status,
        };
    }
}
