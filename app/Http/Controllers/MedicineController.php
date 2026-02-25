<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedicineRequest;
use App\Http\Requests\UpdateMedicineRequest;
use App\Models\Medicine;
use Illuminate\Http\Request;

class MedicineController extends Controller
{
    public function index(Request $request)
    {
        $query = Medicine::with(['category', 'brand']);
        
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        
        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        if ($request->low_stock) {
            // Only show medicines with stock management enabled
            $query->where('manage_stock', true);
        }

        $medicines = $query->latest()->paginate(10);
        $categories = \App\Models\MedicineCategory::active()->orderBy('name')->get();
        
        return view('admin.medicines.index', compact('medicines', 'categories'));
    }

    public function create()
    {
        return view('admin.medicines.create');
    }

    public function store(StoreMedicineRequest $request)
    {
        Medicine::create($request->validated());

        return redirect()->route('medicines.index')->with('success', 'Medicine added successfully.');
    }

    public function edit(Medicine $medicine)
    {
        return view('admin.medicines.edit', compact('medicine'));
    }

    public function update(UpdateMedicineRequest $request, Medicine $medicine)
    {
        $medicine->update($request->validated());

        return redirect()->route('medicines.index')->with('success', 'Medicine updated successfully.');
    }

    public function destroy(Medicine $medicine)
    {
        $medicine->delete();
        return redirect()->route('medicines.index')->with('success', 'Medicine deleted successfully.');
    }
}