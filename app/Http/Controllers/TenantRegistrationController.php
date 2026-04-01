<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterTenantRequest;
use App\Services\TenantProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TenantRegistrationController extends Controller
{
    public function __construct(
        protected TenantProvisioningService $provisioning,
    ) {}

    /**
     * Show the hospital registration form.
     */
    public function create(): View
    {
        $plans = \App\Models\Plan::active()->orderBy('sort_order')->get();
        return view('tenant.register', compact('plans'));
    }

    /**
     * Handle hospital registration and kick off provisioning.
     */
    public function store(RegisterTenantRequest $request): RedirectResponse
    {
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
                async: false,
            );

            return redirect()
                ->route('tenant.provisioning', $tenant)
                ->with('success', 'Your hospital has been set up successfully.');

        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['slug' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Show provisioning status page (polls until active).
     */
    public function provisioning(\App\Models\Tenant $tenant): View
    {
        return view('tenant.provisioning', compact('tenant'));
    }

    /**
     * API endpoint for polling provisioning status.
     */
    public function status(\App\Models\Tenant $tenant)
    {
        return response()->json([
            'status' => $tenant->status,
            'domain' => $tenant->domain,
            'url'    => 'http://' . $tenant->domain . '/login',
        ]);
    }
}
