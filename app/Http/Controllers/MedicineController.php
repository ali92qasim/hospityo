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
        $query = Medicine::query();
        
        if ($request->category) {
            $query->where('category', $request->category);
        }
        
        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        if ($request->low_stock) {
            $query->whereRaw('stock_quantity <= reorder_level');
        }

        $medicines = $query->latest()->paginate(10);
        $categories = Medicine::distinct()->pluck('category');
        
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