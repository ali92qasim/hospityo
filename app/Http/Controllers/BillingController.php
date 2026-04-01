<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\BillingService;
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

                Subscription::create([
                    'tenant_id' => $tenant->id,
                    'plan_id'   => $plan->id,
                    'status'    => 'active',
                    'amount'    => 0,
                    'currency'  => 'PKR',
                    'starts_at' => now(),
                    'ends_at'   => null,
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
            $subscriptionId = $request->query('subscription_id');

            if (! $subscriptionId) {
                return redirect()->route('billing.index')
                    ->with('error', 'Invalid payment callback.');
            }

            $subscription = $this->billing->handleSuccess(
                (int) $subscriptionId,
                $request->all()
            );

            return redirect()->route('billing.index')
                ->with('success', 'Payment successful. Your plan has been upgraded.');

        } catch (\Throwable $e) {
            Log::error('[Billing] Success callback error', ['error' => $e->getMessage()]);
            return redirect()->route('billing.index')
                ->with('error', 'Payment was received but there was an issue updating your plan. Please contact support.');
        }
    }

    /**
     * PayFast cancel/failure callback.
     */
    public function cancel(Request $request)
    {
        try {
            $subscriptionId = $request->query('subscription_id');

            if ($subscriptionId) {
                $this->billing->handleFailure((int) $subscriptionId, $request->all());
            }
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
        } catch (\Throwable $e) {
            Log::error('[Billing] Webhook error', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error'], 500);
        }
    }
}
