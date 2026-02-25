<?php

namespace App\Http\Controllers;

use App\Models\MedicineCategory;
use App\Http\Requests\StoreMedicineCategoryRequest;
use App\Http\Requests\UpdateMedicineCategoryRequest;
use Illuminate\Http\Request;

class MedicineCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = MedicineCategory::query();

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%');
            });
        }

        $categories = $query->orderBy('name')->paginate(15);

        return view('admin.medicine-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.medicine-categories.create');
    }

    public function store(StoreMedicineCategoryRequest $request)
    {
        MedicineCategory::create($request->validated());

        return redirect()->route('medicine-categories.index')
            ->with('success', 'Medicine category created successfully.');
    }

    public function show(MedicineCategory $medicineCategory)
    {
        $medicineCategory->load('medicines');
        
        return view('admin.medicine-categories.show', compact('medicineCategory'));
    }

    public function edit(MedicineCategory $medicineCategory)
    {
        return view('admin.medicine-categories.edit', compact('medicineCategory'));
    }

    public function update(UpdateMedicineCategoryRequest $request, MedicineCategory $medicineCategory)
    {
        $medicineCategory->update($request->validated());

        return redirect()->route('medicine-categories.index')
            ->with('success', 'Medicine category updated successfully.');
    }

    public function destroy(MedicineCategory $medicineCategory)
    {
        if ($medicineCategory->medicines()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete category with associated medicines.']);
        }

        $medicineCategory->delete();

        return redirect()->route('medicine-categories.index')
            ->with('success', 'Medicine category deleted successfully.');
    }
}
