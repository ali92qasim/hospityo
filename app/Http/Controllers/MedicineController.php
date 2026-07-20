<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedicineRequest;
use App\Http\Requests\UpdateMedicineRequest;
use App\Models\Medicine;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class MedicineController extends Controller
{
    public function index()
    {
        $categories = \App\Models\MedicineCategory::active()->orderBy('name')->get();

        return view('admin.medicines.index', compact('categories'));
    }

    public function data(Request $request)
    {
        $query = $this->medicinesIndexQuery($request);

        return DataTables::eloquent($query)
            ->addColumn('stock_quantity', function (Medicine $medicine) {
                return $medicine->manage_stock ? $medicine->getCurrentStock() : null;
            })
            ->addColumn('stock_unit', function (Medicine $medicine) {
                return $medicine->baseUnit?->name ?? '';
            })
            ->addColumn('is_low_stock', function (Medicine $medicine) {
                return $medicine->isLowStock();
            })
            ->filterColumn('category', function ($query, $keyword) {
                $query->whereHas('category', function ($categoryQuery) use ($keyword) {
                    $categoryQuery->where('name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('brand', function ($query, $keyword) {
                $query->whereHas('brand', function ($brandQuery) use ($keyword) {
                    $brandQuery->where('name', 'like', "%{$keyword}%");
                });
            })
            ->toJson();
    }

    private function medicinesIndexQuery(Request $request)
    {
        $query = Medicine::with(['category', 'brand', 'baseUnit']);

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->boolean('low_stock')) {
            $query->where('manage_stock', true);
        }

        return $query;
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