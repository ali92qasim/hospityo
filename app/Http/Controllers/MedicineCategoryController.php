<?php

namespace App\Http\Controllers;

use App\Jobs\Tenant\ImportMedicineCategoriesJob;
use App\Models\MedicineCategory;
use App\Http\Requests\StoreMedicineCategoryRequest;
use App\Http\Requests\UpdateMedicineCategoryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
            $path = $request->file('file')->store('imports/medicine-categories', 'local');
            $cacheKey = 'medicine_category_import_' . auth()->id() . '_' . Str::random(8);

            Cache::put($cacheKey, ['status' => 'pending'], now()->addMinutes(30));

            ImportMedicineCategoriesJob::dispatch($path, $cacheKey, auth()->id())
                ->onConnection('deferred');
        } catch (\Throwable $e) {
            Log::error('[MedicineCategoryImport] Failed to dispatch import job', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to start the import. Please try again.');
        }

        return redirect()->route('medicine-categories.index')
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

        if ($result['status'] === 'pending') {
            return response()->json(['status' => 'pending']);
        }

        Cache::forget($key);

        return response()->json($result);
    }
}
