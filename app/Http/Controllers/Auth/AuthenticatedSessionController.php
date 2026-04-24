<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): View|RedirectResponse
    {
        // Handle token-based login from central login
        if ($request->has('token') && $request->has('email')) {
            return $this->handleTokenLogin($request);
        }

        // Redirect to central login — users should not access subdomain login directly
        $mainDomain = config('app.url');
        return redirect($mainDomain . '/signin');
    }

    /**
     * Auto-login via one-time token from central login.
     */
    protected function handleTokenLogin(Request $request): RedirectResponse
    {
        $email = $request->input('email');
        $token = $request->input('token');

        $tenant = \App\Models\Tenant::current();
        if (!$tenant) {
            return redirect()->route('login');
        }

        // Verify token
        $mapping = \App\Models\TenantUser::where('email', strtolower($email))
            ->where('tenant_id', $tenant->id)
            ->where('login_token', $token)
            ->first();

        if (!$mapping) {
            return redirect()->route('login')->withErrors(['email' => 'Invalid or expired login link.']);
        }

        // Clear the token (one-time use)
        $mapping->update(['login_token' => null]);

        // Find and authenticate the user
        $user = \App\Models\User::where('email', $email)->first();
        if (!$user) {
            return redirect()->route('login')->withErrors(['email' => 'User not found.']);
        }

        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->put('tenant_id', $tenant->id);

        return redirect()->route('dashboard');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Stamp session with current tenant ID for cross-tenant protection
        $tenant = \App\Models\Tenant::current();
        if ($tenant) {
            $request->session()->put('tenant_id', $tenant->id);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect(config('app.url') . '/signin');
    }
}
