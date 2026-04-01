<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $stats = [
                'total_tenants'    => Tenant::count(),
                'active_tenants'   => Tenant::where('status', 'active')->count(),
                'suspended'        => Tenant::where('status', 'suspended')->count(),
                'provisioning'     => Tenant::where('status', 'provisioning')->count(),
                'total_revenue'    => SubscriptionPayment::where('status', 'success')->sum('amount'),
                'monthly_revenue'  => SubscriptionPayment::where('status', 'success')
                    ->where('paid_at', '>=', now()->startOfMonth())
                    ->sum('amount'),
                'active_subs'      => Subscription::where('status', 'active')->count(),
            ];

            $recentTenants = Tenant::with('plan')
                ->latest()
                ->limit(10)
                ->get();

            $recentPayments = SubscriptionPayment::with(['tenant', 'subscription.plan'])
                ->where('status', 'success')
                ->latest('paid_at')
                ->limit(10)
                ->get();

            $planDistribution = Plan::withCount('tenants')
                ->orderBy('sort_order')
                ->get();

        } catch (\Throwable $e) {
            Log::error('[SuperAdmin] Dashboard load failed', ['error' => $e->getMessage()]);
            $stats = array_fill_keys(['total_tenants', 'active_tenants', 'suspended', 'provisioning', 'total_revenue', 'monthly_revenue', 'active_subs'], 0);
            $recentTenants = collect();
            $recentPayments = collect();
            $planDistribution = collect();
        }

        return view('super-admin.dashboard', compact('stats', 'recentTenants', 'recentPayments', 'planDistribution'));
    }
}
