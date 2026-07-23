<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedicineRequest;
use App\Http\Requests\UpdateMedicineRequest;
use App\Jobs\Tenant\ImportMedicinesJob;
use App\Models\Medicine;
use App\Models\Tenant;
use App\Support\BackgroundQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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

        return $query->orderByDesc('id'); // Latest first (newest medicines)
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

    public function import(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:csv,txt,xlsx,xls',
                'max:10240',
            ],
        ], [
            'file.mimes' => 'Only CSV and Excel files (.csv, .xlsx, .xls) are accepted.',
            'file.max' => 'The file must not exceed 10 MB.',
        ]);

        try {
            $tenant = Tenant::current();

            if ($tenant === null) {
                return back()->with('error', 'Could not determine the current clinic. Please refresh and try again.');
            }

            $path = $request->file('file')->store('imports/medicines', 'local');
            $cacheKey = 'medicine_import_' . auth()->id() . '_' . Str::random(8);

            Cache::put($cacheKey, ['status' => 'pending'], now()->addMinutes(30));

            ImportMedicinesJob::dispatch($path, $cacheKey, auth()->id(), $tenant->id);

            BackgroundQueue::processNextJob();
        } catch (\Throwable $e) {
            Log::error('[MedicineImport] Failed to dispatch import job', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to start the import. Please try again.');
        }

        return redirect()->route('medicines.index')
            ->with('import_pending', true)
            ->with('import_cache_key', $cacheKey);
    }

    public function importStatus(Request $request)
    {
        $key = $request->query('key');

        if (! $key) {
            return response()->json(['status' => 'not_found']);
        }

        $result = Cache::get($key);

        if ($result === null) {
            return response()->json(['status' => 'not_found']);
        }

        if (in_array($result['status'], ['pending', 'processing'], true)) {
            return response()->json($result);
        }

        Cache::forget($key);

        return response()->json($result);
    }
}