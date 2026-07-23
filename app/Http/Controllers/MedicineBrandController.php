<?php

namespace App\Http\Controllers;

use App\Jobs\Tenant\ImportMedicineBrandsJob;
use App\Models\MedicineBrand;
use App\Http\Requests\StoreMedicineBrandRequest;
use App\Http\Requests\UpdateMedicineBrandRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
            $path = $request->file('file')->store('imports/medicine-brands', 'local');
            $cacheKey = 'medicine_brand_import_' . auth()->id() . '_' . Str::random(8);

            Cache::put($cacheKey, ['status' => 'pending'], now()->addMinutes(30));

            ImportMedicineBrandsJob::dispatch($path, $cacheKey, auth()->id())
                ->onConnection('deferred');
        } catch (\Throwable $e) {
            Log::error('[MedicineBrandImport] Failed to dispatch import job', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to start the import. Please try again.');
        }

        return redirect()->route('medicine-brands.index')
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
