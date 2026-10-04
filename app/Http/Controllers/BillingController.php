<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidPaymentNotification;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\BillingService;
use App\Services\Payments\PayFastNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    public function __construct(
        protected BillingService $billing,
    ) {}

    /**
     * Show billing/plan management page for the current tenant.
     */
    public function index()
    {
        $tenant = Tenant::current();
        $plans = Plan::active()->orderBy('sort_order')->get();
        $currentSubscription = Subscription::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        return view('admin.billing.index', compact('tenant', 'plans', 'currentSubscription'));
    }

    /**
     * Initiate plan upgrade/subscription via PayFast.
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $tenant = Tenant::current();
        $plan = Plan::findOrFail($request->plan_id);

        if ($plan->price <= 0) {
            // Free plan — assign directly, no payment needed
            try {
                $tenant->update(['plan_id' => $plan->id]);

                $subscription = Subscription::create([
                    'tenant_id' => $tenant->id,
                    'plan_id'   => $plan->id,
                    'status'    => 'active',
                    'amount'    => 0,
                    'currency'  => 'PKR',
                    'starts_at' => now(),
                    'ends_at'   => null,
                ]);

                // Record payment event for history (even for free plan)
                $subscription->payments()->create([
                    'tenant_id'      => $tenant->id,
                    'amount'         => 0,
                    'currency'       => 'PKR',
                    'payment_method' => 'free',
                    'status'         => 'completed',
                    'paid_at'        => now(),
                ]);

                return redirect()->route('billing.index')
                    ->with('success', "Switched to {$plan->name} plan.");
            } catch (\Throwable $e) {
                Log::error('[Billing] Free plan switch failed', ['error' => $e->getMessage()]);
                return redirect()->route('billing.index')
                    ->with('error', 'Failed to switch plan. Please try again.');
            }
        }

        // Paid plan — initiate PayFast checkout
        $result = $this->billing->initiateCheckout($tenant, $plan);

        if (! $result['success']) {
            return redirect()->route('billing.index')
                ->with('error', $result['error']);
        }

        // Redirect to PayFast hosted checkout via a form POST
        return view('admin.billing.payfast-redirect', [
            'checkout_url' => $result['checkout_url'],
            'payload'      => $result['payload'],
        ]);
    }

    /**
     * PayFast success callback.
     */
    public function success(Request $request)
    {
        try {
            $notification = PayFastNotification::verify($request->query());
            $subscription = $this->billing->applyPayFastNotification($notification, (int) Tenant::current()->id);
        } catch (InvalidPaymentNotification $e) {
            Log::warning('[Billing] Rejected PayFast success return', ['error' => $e->getMessage()]);

            return redirect()->route('billing.index')
                ->with('error', 'We could not verify this payment with PayFast, so your plan was not changed. If you were charged, please contact support.');
        } catch (\Throwable $e) {
            Log::error('[Billing] Success callback error', ['error' => $e->getMessage()]);

            return redirect()->route('billing.index')
                ->with('error', 'Payment was received but there was an issue updating your plan. Please contact support.');
        }

        if ($subscription->status !== 'active') {
            return redirect()->route('billing.index')
                ->with('error', 'PayFast did not approve this payment, so your plan was not changed.');
        }

        return redirect()->route('billing.index')
            ->with('success', 'Payment successful. Your plan has been upgraded.');
    }

    /**
     * PayFast cancel/failure callback. Only a PayFast-signed notification for
     * this tenant's own subscription changes anything.
     */
    public function cancel(Request $request)
    {
        try {
            $notification = PayFastNotification::verify($request->query());
            $this->billing->applyPayFastNotification($notification, (int) Tenant::current()->id);
        } catch (InvalidPaymentNotification $e) {
            Log::info('[Billing] Unverified PayFast cancel return ignored', ['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('[Billing] Cancel callback error', ['error' => $e->getMessage()]);
        }

        return redirect()->route('billing.index')
            ->with('error', 'Payment was cancelled or failed. Please try again.');
    }

    /**
     * PayFast IPN webhook (server-to-server).
     */
    public function webhook(Request $request)
    {
        try {
            $this->billing->handleWebhook($request->all());

            return response()->json(['status' => 'ok']);
        } catch (InvalidPaymentNotification $e) {
            Log::warning('[Billing] Rejected PayFast webhook', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'rejected'], 403);
        } catch (\Throwable $e) {
            Log::error('[Billing] Webhook error', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error'], 500);
        }
    }
}
