<?php

namespace App\Http\Controllers;

use App\Models\Tax;
use App\Models\Department;
use Illuminate\Http\Request;

class TaxController extends Controller
{
    public function index()
    {
        $taxes = Tax::with('mappings')->latest()->paginate(15);
        return view('admin.taxes.index', compact('taxes'));
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get();
        return view('admin.taxes.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:tenant.taxes,code',
            'percentage' => 'required|numeric|min:0|max:100',
            'is_inclusive' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string|max:500',
        ]);

        $tax = Tax::create([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'percentage' => $validated['percentage'],
            'is_inclusive' => $request->boolean('is_inclusive'),
            'is_active' => $request->boolean('is_active', true),
            'description' => $validated['description'] ?? null,
        ]);

        $this->saveMappings($tax, $request);

        return redirect()->route('taxes.index')->with('success', 'Tax created successfully.');
    }

    public function edit(Tax $tax)
    {
        $tax->load('mappings');
        $departments = Department::orderBy('name')->get();
        return view('admin.taxes.edit', compact('tax', 'departments'));
    }

    public function update(Request $request, Tax $tax)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:tenant.taxes,code,' . $tax->id,
            'percentage' => 'required|numeric|min:0|max:100',
            'is_inclusive' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string|max:500',
        ]);

        $tax->update([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'percentage' => $validated['percentage'],
            'is_inclusive' => $request->boolean('is_inclusive'),
            'is_active' => $request->boolean('is_active', true),
            'description' => $validated['description'] ?? null,
        ]);

        $tax->mappings()->delete();
        $this->saveMappings($tax, $request);

        return redirect()->route('taxes.index')->with('success', 'Tax updated successfully.');
    }

    public function destroy(Tax $tax)
    {
        $tax->delete();
        return redirect()->route('taxes.index')->with('success', 'Tax deleted successfully.');
    }

    /**
     * Save tax mappings from the simplified form.
     */
    private function saveMappings(Tax $tax, Request $request): void
    {
        if ($request->boolean('apply_global')) {
            $tax->mappings()->create(['applicable_on' => 'all', 'applicable_value' => 'all']);
            return;
        }

        $hasMappings = false;

        // Bill types
        foreach ($request->input('bill_types', []) as $billType) {
            $tax->mappings()->create(['applicable_on' => 'bill_type', 'applicable_value' => $billType]);
            $hasMappings = true;
        }

        // Service categories
        foreach ($request->input('service_categories', []) as $category) {
            $tax->mappings()->create(['applicable_on' => 'service_category', 'applicable_value' => $category]);
            $hasMappings = true;
        }

        // If nothing selected, default to global
        if (!$hasMappings) {
            $tax->mappings()->create(['applicable_on' => 'all', 'applicable_value' => 'all']);
        }
    }

    /**
     * API: Calculate taxes for a bill type (called via AJAX).
     */
    public function calculate(Request $request)
    {
        $billType = $request->input('bill_type');
        $subtotal = (float) $request->input('subtotal', 0);

        $taxes = Tax::getApplicableTaxes($billType);
        $totalTax = 0;
        $breakdown = [];

        foreach ($taxes as $tax) {
            $amount = $tax->calculateTax($subtotal);
            $totalTax += $amount;
            $breakdown[] = [
                'code' => $tax->code,
                'name' => $tax->name,
                'percentage' => $tax->percentage,
                'amount' => round($amount, 2),
            ];
        }

        return response()->json([
            'total_tax' => round($totalTax, 2),
            'breakdown' => $breakdown,
        ]);
    }
}
