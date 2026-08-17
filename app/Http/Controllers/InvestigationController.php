<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLabTestRequest;
use App\Http\Requests\UpdateLabTestRequest;
use App\Jobs\Tenant\ImportInvestigationsJob;
use App\Models\LabTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class InvestigationController extends Controller
{
    public function index(Request $request)
    {
        return redirect()
            ->route('lab.tests.index')
            ->with('info', 'Investigations have moved to Lab Tests and Imaging Studies.');
    }

    public function indexLab(Request $request)
    {
        return view('admin.lab.tests.index');
    }

    public function dataLab(Request $request)
    {
        return DataTables::eloquent(LabTest::query()->orderByDesc('id'))->toJson();
    }

    public function data(Request $request)
    {
        return $this->dataLab($request);
    }

    public function create()
    {
        return view('admin.lab.tests.create');
    }

    public function store(StoreLabTestRequest $request)
    {
        $data = $request->safe()->except('parameters');
        $labTest = LabTest::create($data);

        if ($request->has('parameters')) {
            foreach ($request->parameters as $index => $paramData) {
                if (! empty($paramData['name'])) {
                    $labTest->parameters()->create([
                        'parameter_name'   => $paramData['name'],
                        'unit'             => $paramData['unit'] ?? null,
                        'data_type'        => 'numeric',
                        'reference_ranges' => ! empty($paramData['reference_range']) ? ['range' => $paramData['reference_range']] : null,
                        'display_order'    => $index + 1,
                        'is_active'        => true,
                    ]);
                }
            }
        }

        return redirect()->route('lab.tests.index')->with('success', 'Lab test created successfully.');
    }

    public function show(LabTest $labTest)
    {
        $labTest->load('parameters');
        $investigation = $labTest;

        return view('admin.lab.tests.show', compact('investigation', 'labTest'));
    }

    public function edit(LabTest $labTest)
    {
        $labTest->load('parameters');
        $investigation = $labTest;

        return view('admin.lab.tests.edit', compact('investigation', 'labTest'));
    }

    public function update(UpdateLabTestRequest $request, LabTest $labTest)
    {
        $labTest->update($request->safe()->except('parameters'));

        if ($request->has('parameters')) {
            $labTest->parameters()->delete();

            foreach ($request->parameters as $index => $paramData) {
                if (! empty($paramData['name'])) {
                    $labTest->parameters()->create([
                        'parameter_name'   => $paramData['name'],
                        'unit'             => $paramData['unit'] ?? null,
                        'data_type'        => 'numeric',
                        'reference_ranges' => ! empty($paramData['reference_range']) ? ['range' => $paramData['reference_range']] : null,
                        'display_order'    => $index + 1,
                        'is_active'        => true,
                    ]);
                }
            }
        }

        return redirect()->route('lab.tests.index')->with('success', 'Lab test updated successfully.');
    }

    public function destroy(LabTest $labTest)
    {
        $labTest->delete();

        return redirect()->route('lab.tests.index')->with('success', 'Lab test deleted successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $path     = $request->file('file')->store('imports/investigations', 'local');
        $cacheKey = 'investigation_import_'.auth()->id().'_'.Str::random(8);

        Cache::put($cacheKey, ['status' => 'pending'], now()->addMinutes(30));

        ImportInvestigationsJob::dispatch(
            $path,
            $cacheKey,
            auth()->id(),
            'lab'
        );

        return redirect()->route('lab.tests.index')
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
