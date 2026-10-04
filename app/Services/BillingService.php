<?php

namespace App\Services;

use App\Exceptions\InvalidPaymentNotification;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Services\Payments\PayFastNotification;
use Illuminate\Support\Facades\Log;
use zfhassaan\Payfast\PayFast;

class BillingService
{
    protected ?PayFast $payfast = null;

    /**
     * Resolve PayFast only when a checkout action needs the gateway client.
     * Index/success/cancel/webhook paths must not construct it eagerly.
     */
    protected function payfast(): PayFast
    {
        return $this->payfast ??= app(PayFast::class);
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
            $tokenResponse = $this->payfast()->getToken();
            $tokenData = json_decode($tokenResponse->getContent(), true);

            if (! isset($tokenData['token'])) {
                throw new \RuntimeException('Failed to obtain PayFast auth token: ' . ($tokenData['message'] ?? 'Unknown error'));
            }

            $this->payfast()->setAuthToken($tokenData['token']);

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
            $merchantName = config('app.name', 'UseClinicSync');
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
                'VERSION'                => 'USECLINICSYNC-SAAS-1.0',
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
     * Apply a PayFast notification that has already been verified.
     *
     * Resolves the subscription from the signed basket_id, requires that basket to be
     * the one issued at checkout, and (for browser returns) that it belongs to $tenantId.
     * Only a pending subscription changes state, so replays can't revive or extend it.
     *
     * @throws InvalidPaymentNotification
     */
    public function applyPayFastNotification(PayFastNotification $notification, ?int $tenantId = null): Subscription
    {
        $subscription = $this->subscriptionForBasket($notification->basketId);

        if ($tenantId !== null && (int) $subscription->tenant_id !== $tenantId) {
            throw new InvalidPaymentNotification('PayFast notification is for another tenant\'s subscription.');
        }

        if ($subscription->status !== 'pending') {
            return $subscription;
        }

        return $notification->approved()
            ? $this->handleSuccess($subscription, $notification->raw)
            : $this->handleFailure($subscription, $notification->raw);
    }

    /**
     * @throws InvalidPaymentNotification
     */
    protected function subscriptionForBasket(string $basketId): Subscription
    {
        if (! preg_match('/^SUB-(\d+)-\d+$/', $basketId, $m)) {
            throw new InvalidPaymentNotification('Unrecognised PayFast basket_id.');
        }

        $subscription = Subscription::find((int) $m[1]);
        $issuedBasket = (string) ($subscription?->payfast_meta['basket_id'] ?? '');

        if (! $subscription || $issuedBasket === '' || ! hash_equals($issuedBasket, $basketId)) {
            throw new InvalidPaymentNotification('PayFast basket_id was not issued for this subscription.');
        }

        return $subscription;
    }

    /**
     * Mark a pending subscription paid. Only reachable through a verified notification.
     */
    protected function handleSuccess(Subscription $subscription, array $payfastData): Subscription
    {
        $subscriptionId = $subscription->id;

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
     * Mark a pending subscription failed. Only reachable through a verified notification.
     */
    protected function handleFailure(Subscription $subscription, array $payfastData): Subscription
    {
        $subscriptionId = $subscription->id;

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
     * Handle the PayFast IPN (server-to-server). Unsigned or unrecognised
     * notifications throw InvalidPaymentNotification and change nothing.
     *
     * @throws InvalidPaymentNotification
     */
    public function handleWebhook(array $data): Subscription
    {
        return $this->applyPayFastNotification(PayFastNotification::verify($data));
    }
}
