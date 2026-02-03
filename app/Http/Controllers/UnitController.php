<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        $units = Unit::with('baseUnit')->latest()->paginate(15);
        return view('admin.units.index', compact('units'));
    }

    public function create()
    {
        $baseUnits = Unit::baseUnits()->active()->get();
        return view('admin.units.create', compact('baseUnits'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'abbreviation' => 'required|string|max:10|unique:units',
            'base_unit_id' => 'nullable|exists:units,id',
            'conversion_factor' => 'required|numeric|min:0.0001',
            'type' => 'required|in:solid,liquid,gas,packaging',
            'is_active' => 'boolean'
        ]);

        Unit::create($validated);

        return redirect()->route('units.index')
            ->with('success', 'Unit created successfully.');
    }

    public function edit(Unit $unit)
    {
        $baseUnits = Unit::baseUnits()->active()->where('id', '!=', $unit->id)->get();
        return view('admin.units.edit', compact('unit', 'baseUnits'));
    }

    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'abbreviation' => 'required|string|max:10|unique:units,abbreviation,' . $unit->id,
            'base_unit_id' => 'nullable|exists:units,id',
            'conversion_factor' => 'required|numeric|min:0.0001',
            'type' => 'required|in:solid,liquid,gas,packaging',
            'is_active' => 'boolean'
        ]);

        $unit->update($validated);

        return redirect()->route('units.index')
            ->with('success', 'Unit updated successfully.');
    }

    public function destroy(Unit $unit)
    {
        if ($unit->medicines()->exists() || $unit->derivedUnits()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete unit that is in use.']);
        }

        $unit->delete();

        return redirect()->route('units.index')
            ->with('success', 'Unit deleted successfully.');
    }
}