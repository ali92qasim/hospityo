<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ModuleRegistry;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    public function index()
    {
        try {
            $plans = Plan::withCount('tenants')->orderBy('sort_order')->get();
            return view('super-admin.plans.index', compact('plans'));
        } catch (\Throwable $e) {
            Log::error('[SuperAdmin] Plans list failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to load plans.');
        }
    }

    public function create()
    {
        $modules = ModuleRegistry::definitions();
        return view('super-admin.plans.create', compact('modules'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'slug'          => 'nullable|string|max:255|unique:plans,slug',
            'description'   => 'nullable|string|max:500',
            'price'         => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,yearly,lifetime',
            'modules'       => 'required|array|min:1',
            'modules.*'     => 'string',
            'max_users'     => 'nullable|integer|min:1',
            'max_patients'  => 'nullable|integer|min:1',
            'max_doctors'   => 'nullable|integer|min:1',
            'sort_order'    => 'nullable|integer|min:0',
            'is_active'     => 'boolean',
        ]);

        try {
            Plan::create([
                'name'          => $validated['name'],
                'slug'          => $validated['slug'] ?: Str::slug($validated['name']),
                'description'   => $validated['description'],
                'price'         => $validated['price'],
                'billing_cycle' => $validated['billing_cycle'],
                'modules'       => $validated['modules'],
                'limits'        => array_filter([
                    'max_users'    => $validated['max_users'] ?? null,
                    'max_patients' => $validated['max_patients'] ?? null,
                    'max_doctors'  => $validated['max_doctors'] ?? null,
                ]),
                'sort_order'    => $validated['sort_order'] ?? 0,
                'is_active'     => $request->boolean('is_active', true),
            ]);

            return redirect()->route('super-admin.plans.index')->with('success', 'Plan created.');
        } catch (\Throwable $e) {
            Log::error('[SuperAdmin] Plan create failed', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to create plan.');
        }
    }

    public function edit(Plan $plan)
    {
        $modules = ModuleRegistry::definitions();
        return view('super-admin.plans.edit', compact('plan', 'modules'));
    }

    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'slug'          => 'nullable|string|max:255|unique:plans,slug,' . $plan->id,
            'description'   => 'nullable|string|max:500',
            'price'         => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,yearly,lifetime',
            'modules'       => 'required|array|min:1',
            'modules.*'     => 'string',
            'max_users'     => 'nullable|integer|min:1',
            'max_patients'  => 'nullable|integer|min:1',
            'max_doctors'   => 'nullable|integer|min:1',
            'sort_order'    => 'nullable|integer|min:0',
            'is_active'     => 'boolean',
        ]);

        try {
            $plan->update([
                'name'          => $validated['name'],
                'slug'          => $validated['slug'] ?: Str::slug($validated['name']),
                'description'   => $validated['description'],
                'price'         => $validated['price'],
                'billing_cycle' => $validated['billing_cycle'],
                'modules'       => $validated['modules'],
                'limits'        => array_filter([
                    'max_users'    => $validated['max_users'] ?? null,
                    'max_patients' => $validated['max_patients'] ?? null,
                    'max_doctors'  => $validated['max_doctors'] ?? null,
                ]),
                'sort_order'    => $validated['sort_order'] ?? 0,
                'is_active'     => $request->boolean('is_active', true),
            ]);

            return redirect()->route('super-admin.plans.index')->with('success', 'Plan updated.');
        } catch (\Throwable $e) {
            Log::error('[SuperAdmin] Plan update failed', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to update plan.');
        }
    }

    public function destroy(Plan $plan)
    {
        try {
            if ($plan->tenants()->count() > 0) {
                return back()->with('error', "Cannot delete '{$plan->name}' — {$plan->tenants()->count()} hospitals are using it.");
            }

            $plan->delete();
            return redirect()->route('super-admin.plans.index')->with('success', 'Plan deleted.');
        } catch (\Throwable $e) {
            Log::error('[SuperAdmin] Plan delete failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to delete plan.');
        }
    }
}
