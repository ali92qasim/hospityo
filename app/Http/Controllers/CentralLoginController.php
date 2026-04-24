<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class CentralLoginController extends Controller
{
    public function showLogin()
    {
        // If already on a tenant subdomain, use the tenant login
        if (Tenant::current()) {
            return redirect('/login');
        }

        return view('auth.central-login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $email = strtolower($request->email);

        // Find which tenant(s) this email belongs to
        $tenant = TenantUser::findTenantByEmail($email);

        if (!$tenant) {
            return back()->withErrors(['email' => 'No account found with this email address.'])->withInput();
        }

        // Verify password against the tenant's database
        try {
            $tenant->makeCurrent();

            $user = DB::connection('tenant')
                ->table('users')
                ->where('email', $email)
                ->first();

            Tenant::forgetCurrent();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return back()->withErrors(['email' => 'Invalid email or password.'])->withInput();
            }

            // Build the redirect URL to the tenant's subdomain login
            $protocol = parse_url(config('app.url'), PHP_URL_SCHEME) ?? 'https';
            $tenantUrl = $protocol . '://' . $tenant->domain . '/login';

            // Store a one-time token for seamless login on the subdomain
            $token = bin2hex(random_bytes(32));
            DB::connection('landlord')->table('tenant_users')
                ->where('email', $email)
                ->where('tenant_id', $tenant->id)
                ->update(['login_token' => $token, 'updated_at' => now()]);

            return redirect($tenantUrl . '?token=' . $token . '&email=' . urlencode($email));

        } catch (\Throwable $e) {
            Tenant::forgetCurrent();
            Log::error('[CentralLogin] Failed', ['email' => $email, 'error' => $e->getMessage()]);
            return back()->withErrors(['email' => 'Something went wrong. Please try again.'])->withInput();
        }
    }
}
