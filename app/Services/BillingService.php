<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use zfhassaan\Payfast\PayFast;

class BillingService
{
    protected PayFast $payfast;

    public function __construct()
    {
        $this->payfast = new PayFast();
    }

    /**
     * Initiate a subscription checkout via PayFast Hosted Checkout.
     *
     * Creates a local subscription record in 'pending' state,
     * then returns the PayFast redirect URL + payload.
     */
    public function initiateCheckout(Tenant $tenant, Plan $plan): array
    {
        try {
            // Get auth token from PayFast
            $tokenResponse = $this->payfast->getToken();
            $tokenData = json_decode($tokenResponse->getContent(), true);

            if (! isset($tokenData['token'])) {
                throw new \RuntimeException('Failed to obtain PayFast auth token: ' . ($tokenData['message'] ?? 'Unknown error'));
            }

            $this->payfast->setAuthToken($tokenData['token']);

            // Create local subscription record
            $subscription = Subscription::create([
                'tenant_id'  => $tenant->id,
                'plan_id'    => $plan->id,
                'status'     => 'pending',
                'amount'     => $plan->price,
                'currency'   => 'PKR',
                'starts_at'  => now(),
                'ends_at'    => now()->addMonth(),
            ]);

            // Build PayFast hosted checkout payload
            $merchantId   = config('payfast.merchant_id');
            $merchantName = config('app.name', 'Hospityo');
            $orderId      = 'SUB-' . $subscription->id . '-' . time();
            $amount       = (int) ($plan->price * 100); // PayFast expects paisa
            $signature    = md5($merchantId . ':' . $merchantName . ':' . $amount . ':' . $orderId);

            $successUrl = url('/billing/payfast/success?subscription_id=' . $subscription->id);
            $failUrl    = url('/billing/payfast/cancel?subscription_id=' . $subscription->id);
            $callbackUrl = 'signature=' . $signature . '&order_id=' . $orderId;

            $payload = [
                'MERCHANT_ID'            => $merchantId,
                'MERCHANT_NAME'          => $merchantName,
                'TOKEN'                  => $tokenData['token'],
                'PROCCODE'               => '00',
                'TXNAMT'                 => $amount,
                'CUSTOMER_MOBILE_NO'     => $tenant->phone ?? '',
                'CUSTOMER_EMAIL_ADDRESS' => $tenant->email ?? '',
                'SIGNATURE'              => $signature,
                'VERSION'                => 'HOSPITYO-SAAS-1.0',
                'TXNDESC'                => "Subscription: {$plan->name} plan for {$tenant->name}",
                'SUCCESS_URL'            => urlencode($successUrl),
                'FAILURE_URL'            => urlencode($failUrl),
                'BASKET_ID'              => $orderId,
                'ORDER_DATE'             => now()->format('Y-m-d H:i:s'),
                'CHECKOUT_URL'           => urlencode($callbackUrl),
            ];

            // Store the order ID on the subscription for later verification
            $subscription->update([
                'payfast_meta' => ['basket_id' => $orderId, 'signature' => $signature],
            ]);

            $checkoutUrl = config('payfast.mode') === 'sandbox'
                ? config('payfast.sandbox_api_url')
                : config('payfast.api_url');

            return [
                'success'      => true,
                'checkout_url' => $checkoutUrl,
                'payload'      => $payload,
                'subscription' => $subscription,
            ];

        } catch (\Throwable $e) {
            Log::error('[Billing] Checkout initiation failed', [
                'tenant_id' => $tenant->id,
                'plan_id'   => $plan->id,
                'error'     => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error'   => 'Payment initiation failed. Please try again.',
            ];
        }
    }

    /**
     * Handle successful payment callback from PayFast.
     */
    public function handleSuccess(int $subscriptionId, array $payfastData): Subscription
    {
        $subscription = Subscription::findOrFail($subscriptionId);

        try {
            $subscription->update([
                'status'                  => 'active',
                'payfast_transaction_id'  => $payfastData['transaction_id'] ?? null,
                'starts_at'               => now(),
                'ends_at'                 => now()->addMonth(),
                'payfast_meta'            => array_merge(
                    $subscription->payfast_meta ?? [],
                    ['success_response' => $payfastData]
                ),
            ]);

            // Record the payment
            SubscriptionPayment::create([
                'subscription_id'       => $subscription->id,
                'tenant_id'             => $subscription->tenant_id,
                'payfast_transaction_id' => $payfastData['transaction_id'] ?? null,
                'status'                => 'success',
                'amount'                => $subscription->amount,
                'currency'              => $subscription->currency,
                'payment_method'        => $payfastData['payment_method'] ?? 'card',
                'payfast_response'      => $payfastData,
                'paid_at'               => now(),
            ]);

            // Update tenant's plan
            $subscription->tenant->update([
                'plan_id' => $subscription->plan_id,
            ]);

            Log::info('[Billing] Payment successful', [
                'subscription_id' => $subscription->id,
                'tenant_id'       => $subscription->tenant_id,
            ]);

        } catch (\Throwable $e) {
            Log::error('[Billing] Failed to process success callback', [
                'subscription_id' => $subscriptionId,
                'error'           => $e->getMessage(),
            ]);
            throw $e;
        }

        return $subscription;
    }

    /**
     * Handle failed/cancelled payment callback.
     */
    public function handleFailure(int $subscriptionId, array $payfastData): Subscription
    {
        $subscription = Subscription::findOrFail($subscriptionId);

        try {
            $subscription->update([
                'status'       => 'cancelled',
                'cancelled_at' => now(),
                'payfast_meta' => array_merge(
                    $subscription->payfast_meta ?? [],
                    ['failure_response' => $payfastData]
                ),
            ]);

            SubscriptionPayment::create([
                'subscription_id'       => $subscription->id,
                'tenant_id'             => $subscription->tenant_id,
                'payfast_transaction_id' => $payfastData['transaction_id'] ?? null,
                'status'                => 'failed',
                'amount'                => $subscription->amount,
                'currency'              => $subscription->currency,
                'payfast_response'      => $payfastData,
            ]);

            Log::warning('[Billing] Payment failed/cancelled', [
                'subscription_id' => $subscription->id,
                'tenant_id'       => $subscription->tenant_id,
            ]);

        } catch (\Throwable $e) {
            Log::error('[Billing] Failed to process failure callback', [
                'subscription_id' => $subscriptionId,
                'error'           => $e->getMessage(),
            ]);
            throw $e;
        }

        return $subscription;
    }

    /**
     * Handle IPN (Instant Payment Notification) webhook from PayFast.
     */
    public function handleWebhook(array $data): void
    {
        try {
            $basketId = $data['basket_id'] ?? $data['BASKET_ID'] ?? null;

            if (! $basketId) {
                Log::warning('[Billing] Webhook received without basket_id', $data);
                return;
            }

            // Extract subscription ID from basket_id format: SUB-{id}-{timestamp}
            $parts = explode('-', $basketId);
            if (count($parts) < 2 || $parts[0] !== 'SUB') {
                Log::warning('[Billing] Invalid basket_id format in webhook', $data);
                return;
            }

            $subscriptionId = (int) $parts[1];
            $subscription = Subscription::find($subscriptionId);

            if (! $subscription) {
                Log::warning('[Billing] Subscription not found for webhook', ['subscription_id' => $subscriptionId]);
                return;
            }

            $status = $data['status'] ?? $data['STATUS'] ?? '';
            $isSuccess = in_array($status, ['00', 'success', 'SUCCESS', 'COMPLETED']);

            if ($isSuccess) {
                $this->handleSuccess($subscriptionId, $data);
            } else {
                $this->handleFailure($subscriptionId, $data);
            }

        } catch (\Throwable $e) {
            Log::error('[Billing] Webhook processing failed', [
                'error' => $e->getMessage(),
                'data'  => $data,
            ]);
        }
    }
}
