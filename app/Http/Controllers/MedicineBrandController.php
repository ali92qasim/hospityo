<?php

namespace App\Http\Controllers;

use App\Models\MedicineBrand;
use App\Http\Requests\StoreMedicineBrandRequest;
use App\Http\Requests\UpdateMedicineBrandRequest;
use Illuminate\Http\Request;

class MedicineBrandController extends Controller
{
    public function index(Request $request)
    {
        $query = MedicineBrand::query();

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        $brands = $query->orderBy('name')->paginate(15);

        return view('admin.medicine-brands.index', compact('brands'));
    }

    public function create()
    {
        return view('admin.medicine-brands.create');
    }

    public function store(StoreMedicineBrandRequest $request)
    {
        MedicineBrand::create($request->validated());

        return redirect()->route('medicine-brands.index')
            ->with('success', 'Medicine brand created successfully.');
    }

    public function show(MedicineBrand $medicineBrand)
    {
        $medicineBrand->load('medicines');
        
        return view('admin.medicine-brands.show', compact('medicineBrand'));
    }

    public function edit(MedicineBrand $medicineBrand)
    {
        return view('admin.medicine-brands.edit', compact('medicineBrand'));
    }

    public function update(UpdateMedicineBrandRequest $request, MedicineBrand $medicineBrand)
    {
        $medicineBrand->update($request->validated());

        return redirect()->route('medicine-brands.index')
            ->with('success', 'Medicine brand updated successfully.');
    }

    public function destroy(MedicineBrand $medicineBrand)
    {
        if ($medicineBrand->medicines()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete brand with associated medicines.']);
        }

        $medicineBrand->delete();

        return redirect()->route('medicine-brands.index')
            ->with('success', 'Medicine brand deleted successfully.');
    }
}
