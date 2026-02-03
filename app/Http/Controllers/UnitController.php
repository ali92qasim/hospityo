<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Unit;

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

    public function store(StoreUnitRequest $request)
    {
        Unit::create($request->validated());

        return redirect()->route('units.index')
            ->with('success', 'Unit created successfully.');
    }

    public function edit(Unit $unit)
    {
        $baseUnits = Unit::baseUnits()->active()->where('id', '!=', $unit->id)->get();
        return view('admin.units.edit', compact('unit', 'baseUnits'));
    }

    public function update(UpdateUnitRequest $request, Unit $unit)
    {
        $unit->update($request->validated());

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