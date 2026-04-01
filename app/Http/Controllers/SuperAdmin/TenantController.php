<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Tenant::with('plan');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('slug', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('plan_id')) {
                $query->where('plan_id', $request->plan_id);
            }

            $tenants = $query->latest()->paginate(20)->withQueryString();
            $plans = Plan::orderBy('sort_order')->get();

            return view('super-admin.tenants.index', compact('tenants', 'plans'));
        } catch (\Throwable $e) {
            Log::error('[SuperAdmin] Tenant list failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to load tenants.');
        }
    }

    public function show(Tenant $tenant)
    {
        try {
            $tenant->load(['plan', 'subscriptions.plan', 'subscriptions.payments']);

            // Get usage stats from tenant DB
            $usage = $this->getTenantUsage($tenant);

            return view('super-admin.tenants.show', compact('tenant', 'usage'));
        } catch (\Throwable $e) {
            Log::error('[SuperAdmin] Tenant show failed', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to load tenant details.');
        }
    }

    public function suspend(Tenant $tenant)
    {
        try {
            $tenant->update(['status' => 'suspended']);

            Log::info('[SuperAdmin] Tenant suspended', ['tenant_id' => $tenant->id, 'slug' => $tenant->slug]);

            return back()->with('success', "{$tenant->name} has been suspended.");
        } catch (\Throwable $e) {
            Log::error('[SuperAdmin] Tenant suspend failed', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to suspend tenant.');
        }
    }

    public function activate(Tenant $tenant)
    {
        try {
            $tenant->update(['status' => 'active']);

            Log::info('[SuperAdmin] Tenant activated', ['tenant_id' => $tenant->id, 'slug' => $tenant->slug]);

            return back()->with('success', "{$tenant->name} has been activated.");
        } catch (\Throwable $e) {
            Log::error('[SuperAdmin] Tenant activate failed', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to activate tenant.');
        }
    }

    public function changePlan(Request $request, Tenant $tenant)
    {
        $request->validate(['plan_id' => 'required|exists:plans,id']);

        try {
            $newPlan = Plan::findOrFail($request->plan_id);
            $oldPlan = $tenant->plan;

            DB::transaction(function () use ($tenant, $newPlan) {
                $tenant->update(['plan_id' => $newPlan->id]);

                // Cancel existing active subscription
                Subscription::where('tenant_id', $tenant->id)
                    ->where('status', 'active')
                    ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

                // Create new subscription record
                Subscription::create([
                    'tenant_id' => $tenant->id,
                    'plan_id'   => $newPlan->id,
                    'status'    => 'active',
                    'amount'    => $newPlan->price,
                    'currency'  => 'PKR',
                    'starts_at' => now(),
                    'ends_at'   => $newPlan->price > 0 ? now()->addMonth() : null,
                ]);
            });

            $action = ($oldPlan && $newPlan->sort_order > $oldPlan->sort_order) ? 'upgraded' : 'changed';
            Log::info("[SuperAdmin] Tenant plan {$action}", [
                'tenant_id' => $tenant->id,
                'old_plan'  => $oldPlan?->slug,
                'new_plan'  => $newPlan->slug,
            ]);

            return back()->with('success', "{$tenant->name} plan {$action} to {$newPlan->name}.");
        } catch (\Throwable $e) {
            Log::error('[SuperAdmin] Plan change failed', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to change plan.');
        }
    }

    /**
     * Get usage statistics from a tenant's database.
     */
    protected function getTenantUsage(Tenant $tenant): array
    {
        $defaults = [
            'users' => 0, 'patients' => 0, 'doctors' => 0,
            'visits' => 0, 'appointments' => 0, 'bills' => 0,
            'db_size' => '0 KB',
        ];

        try {
            if (! file_exists($tenant->database)) {
                return $defaults;
            }

            $tenant->makeCurrent();

            $usage = [
                'users'        => DB::connection('tenant')->table('users')->count(),
                'patients'     => DB::connection('tenant')->table('patients')->count(),
                'doctors'      => DB::connection('tenant')->table('doctors')->count(),
                'visits'       => DB::connection('tenant')->table('visits')->count(),
                'appointments' => DB::connection('tenant')->table('appointments')->count(),
                'bills'        => DB::connection('tenant')->table('bills')->count(),
                'db_size'      => $this->formatBytes(filesize($tenant->database)),
            ];

            Tenant::forgetCurrent();

            return $usage;
        } catch (\Throwable $e) {
            Tenant::forgetCurrent();
            Log::warning('[SuperAdmin] Usage stats failed for tenant', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
            return $defaults;
        }
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
