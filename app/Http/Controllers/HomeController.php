<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\SiteSetting;
use App\Models\Tenant;

class HomeController extends Controller
{
    public function index()
    {
        $tenant = Tenant::current();
        if ($tenant) {
            if ($tenant->status === 'provisioning') {
                return redirect(config('app.url').'/register/'.$tenant->id.'/provisioning');
            }

            if ($tenant->status === 'failed') {
                return response()->view('errors.tenant-failed', ['tenant' => $tenant], 500);
            }

            if ($tenant->status === 'suspended') {
                return response()->view('errors.tenant-suspended', ['tenant' => $tenant], 403);
            }

            if (auth()->check()) {
                return redirect()->route('dashboard');
            }

            return redirect(config('app.url').'/signin');
        }

        try {
            $landingPlans = Plan::active()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        } catch (\Throwable $e) {
            $landingPlans = null;
        }

        $salesEmail = SiteSetting::get('sales_contact_email');

        return view('landing', compact('landingPlans', 'salesEmail'));
    }
}
