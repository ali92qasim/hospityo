<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterTenantRequest;
use App\Models\Tenant;
use App\Services\TenantProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TenantRegistrationController extends Controller
{
    public function __construct(
        protected TenantProvisioningService $provisioning,
    ) {}

    public function create(): View
    {
        $plans = \App\Models\Plan::active()->orderBy('sort_order')->get();
        return view('tenant.register', compact('plans'));
    }

    public function store(RegisterTenantRequest $request): RedirectResponse
    {
        $slug = $request->slug ?: Str::slug($request->hospital_name);
        $existing = Tenant::where('slug', $slug)->first();

        if ($existing) {
            if (in_array($existing->status, ['provisioning', 'active'])) {
                return redirect()->route('tenant.provisioning', $existing);
            }
            if ($existing->status === 'failed') {
                try { $existing->delete(); } catch (\Throwable $e) {
                    Log::warning('[Registration] Cleanup failed', ['slug' => $slug]);
                }
            }
        }

        try {
            $tenant = $this->provisioning->provision(
                data: [
                    'name'           => $request->hospital_name,
                    'slug'           => $request->slug,
                    'email'          => $request->email,
                    'phone'          => $request->phone,
                    'admin_name'     => $request->admin_name,
                    'admin_email'    => $request->admin_email,
                    'admin_password' => $request->admin_password,
                    'plan'           => $request->plan ?? 'starter',
                ],
                async: true, // Background queue — returns instantly
            );

            return redirect()->route('tenant.provisioning', $tenant);

        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['slug' => $e->getMessage()])->withInput();
        } catch (\Throwable $e) {
            Log::error('[Registration] Provisioning failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Something went wrong. Please try again.')->withInput();
        }
    }

    public function provisioning(Tenant $tenant): View
    {
        return view('tenant.provisioning', compact('tenant'));
    }

    public function status(Tenant $tenant)
    {
        return response()->json([
            'status' => $tenant->fresh()->status,
            'domain' => $tenant->domain,
            'url'    => 'http://' . $tenant->domain . '/login',
        ]);
    }
}
