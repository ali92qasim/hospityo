<?php

namespace App\Http\Controllers;

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'category' => 'required|string|max:255',
            'dosage_form' => 'required|string|max:255',
            'strength' => 'required|string|max:255',
            'unit_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'expiry_date' => 'required|date|after:today',
            'batch_number' => 'required|string|max:255',
            'manufacturer' => 'required|string|max:255',
            'status' => 'required|in:active,inactive'
        ]);

        Medicine::create($validated);

        return redirect()->route('medicines.index')->with('success', 'Medicine added successfully.');
    }

    public function edit(Medicine $medicine)
    {
        return view('admin.medicines.edit', compact('medicine'));
    }

    public function update(Request $request, Medicine $medicine)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'category' => 'required|string|max:255',
            'dosage_form' => 'required|string|max:255',
            'strength' => 'required|string|max:255',
            'unit_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'expiry_date' => 'required|date',
            'batch_number' => 'required|string|max:255',
            'manufacturer' => 'required|string|max:255',
            'status' => 'required|in:active,inactive'
        ]);

        $medicine->update($validated);

        return redirect()->route('medicines.index')->with('success', 'Medicine updated successfully.');
    }

    public function destroy(Medicine $medicine)
    {
        $medicine->delete();
        return redirect()->route('medicines.index')->with('success', 'Medicine deleted successfully.');
    }
}