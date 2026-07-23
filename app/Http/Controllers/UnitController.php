<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Jobs\Tenant\ImportUnitsJob;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class UnitController extends Controller
{
    public function index()
    {
        return view('admin.units.index');
    }

    public function data()
    {
        $query = Unit::query()
            ->with('baseUnit')
            ->orderByDesc('id');

        return DataTables::eloquent($query)
            ->filterColumn('base_unit', function ($query, $keyword) {
                $query->whereHas('baseUnit', function ($baseUnitQuery) use ($keyword) {
                    $baseUnitQuery->where('name', 'like', "%{$keyword}%")
                        ->orWhere('abbreviation', 'like', "%{$keyword}%");
                });
            })
            ->toJson();
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
            $path = $request->file('file')->store('imports/units', 'local');
            $cacheKey = 'unit_import_' . auth()->id() . '_' . Str::random(8);

            Cache::put($cacheKey, ['status' => 'pending'], now()->addMinutes(30));

            ImportUnitsJob::dispatch($path, $cacheKey, auth()->id())
                ->onConnection('deferred');
        } catch (\Throwable $e) {
            Log::error('[UnitImport] Failed to dispatch import job', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to start the import. Please try again.');
        }

        return redirect()->route('units.index')
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