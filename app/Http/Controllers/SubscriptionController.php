<?php

namespace App\Http\Controllers;

use App\Models\PaymentGateway;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
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

        return view('admin.subscription.index', compact('tenant', 'plans', 'currentSubscription', 'gateway'));
    }

    /**
     * Activate subscription after successful Paddle checkout.
     * Called via AJAX from the client after checkout.completed event.
     */
    public function activate(Request $request)
    {
        try {
            $tenant = Tenant::current();
            $gateway = PaymentGateway::enabled()->first();

            if (!$tenant || !$gateway) {
                return response()->json(['success' => false, 'message' => 'Invalid request'], 400);
            }

            $transactionId = $request->input('transaction_id');
            $subscriptionId = $request->input('subscription_id');
            $customerId = $request->input('customer_id');

            if (!$transactionId) {
                return response()->json(['success' => false, 'message' => 'Missing transaction ID'], 400);
            }

            // Try to get transaction details from Paddle API
            $apiKey = $gateway->getCredential('api_key');
            $baseUrl = $gateway->isSandbox() ? 'https://sandbox-api.paddle.com' : 'https://api.paddle.com';

            $transactionData = null;
            if ($apiKey) {
                try {
                    $response = Http::withToken($apiKey)
                        ->get("{$baseUrl}/transactions/{$transactionId}");

                    if ($response->successful()) {
                        $transactionData = $response->json('data');
                    }
                } catch (\Throwable $e) {
                    Log::warning('[Subscription] Paddle API call failed', ['error' => $e->getMessage()]);
                }
            }

            // Determine the plan from transaction data or from tenant's current plan
            $plan = null;
            if ($transactionData && isset($transactionData['items'][0]['price']['id'])) {
                $priceId = $transactionData['items'][0]['price']['id'];
                $plan = Plan::where('paddle_price_id', $priceId)->first();
            }
            $plan = $plan ?? $tenant->plan;

            // Determine subscription ID
            $subId = $subscriptionId ?? ($transactionData['subscription_id'] ?? null);

            // Create or update subscription
            $subscription = Subscription::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'gateway' => 'paddle',
                    'gateway_subscription_id' => $subId,
                ],
                [
                    'plan_id' => $plan?->id,
                    'gateway_customer_id' => $customerId ?? ($transactionData['customer_id'] ?? null),
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
        $gateway = PaymentGateway::where('slug', 'paddle')->first();
        if (!$gateway || !$gateway->is_enabled) {
            return response('Gateway not configured', 400);
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
