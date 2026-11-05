<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterTenantRequest;
use App\Models\Tenant;
use App\Services\TenantProvisioningService;
use Illuminate\Http\JsonResponse;
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

    public function store(RegisterTenantRequest $request): RedirectResponse|JsonResponse
    {
        $slug = $request->slug ?: Str::slug($request->hospital_name);
        $existing = Tenant::where('slug', $slug)->first();

        if ($existing) {
            if (in_array($existing->status, ['provisioning', 'active'])) {
                return $this->registrationSuccess($request, $existing);
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
                // Shared hosts often kill PHP right after the HTTP response is sent,
                // so deferred/queue workers never finish and signup stays on "Finalizing".
                // Run create → migrate → seed inline (~15–30s), then redirect.
                async: false,
            );

            return $this->registrationSuccess($request, $tenant);

        } catch (\InvalidArgumentException $e) {
            return $this->registrationFailure($request, ['slug' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('[Registration] Provisioning failed', ['error' => $e->getMessage()]);

            return $this->registrationFailure(
                $request,
                [],
                'Something went wrong. Please try again.'
            );
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

    /**
     * Browser fetch submits expect JSON so the page can show a custom progress
     * UI without triggering the browser tab loading spinner (full-page POST).
     */
    protected function registrationSuccess(RegisterTenantRequest $request, Tenant $tenant): RedirectResponse|JsonResponse
    {
        $url = route('tenant.provisioning', $tenant);

        if ($request->expectsJson()) {
            return response()->json([
                'redirect' => $url,
                'status' => $tenant->status,
            ]);
        }

        return redirect()->to($url);
    }

    /**
     * @param  array<string, string>  $errors
     */
    protected function registrationFailure(
        RegisterTenantRequest $request,
        array $errors = [],
        ?string $message = null,
    ): RedirectResponse|JsonResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message ?? ($errors['slug'] ?? 'Registration failed.'),
                'errors' => $errors,
            ], 422);
        }

        if ($message) {
            return back()->with('error', $message)->withInput();
        }

        return back()->withErrors($errors)->withInput();
    }
}
