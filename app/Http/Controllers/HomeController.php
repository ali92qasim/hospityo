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
            if (auth()->check()) {
                return redirect()->route('dashboard');
            }

            return redirect(config('app.url') . '/signin');
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
