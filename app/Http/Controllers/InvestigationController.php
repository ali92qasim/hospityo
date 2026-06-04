<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLabTestRequest;
use App\Http\Requests\UpdateLabTestRequest;
use App\Jobs\Tenant\ImportInvestigationsJob;
use App\Models\Investigation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class InvestigationController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.lab.tests.index');
    }
    public function data()
    {
        $query = Investigation::query();

        return DataTables::eloquent($query)
            ->toJson();
    }
    public function create()
    {
        return view('admin.lab.tests.create');
    }

    public function store(StoreLabTestRequest $request)
    {
        $investigation = Investigation::create($request->validated());

        if ($request->has('parameters')) {
            foreach ($request->parameters as $index => $paramData) {
                if (!empty($paramData['name'])) {
                    $investigation->parameters()->create([
                        'parameter_name'   => $paramData['name'],
                        'unit'             => $paramData['unit'] ?? null,
                        'data_type'        => 'numeric',
                        'reference_ranges' => !empty($paramData['reference_range']) ? ['range' => $paramData['reference_range']] : null,
                        'display_order'    => $index + 1,
                        'is_active'        => true,
                    ]);
                }
            }
        }

        return redirect()->route('investigations.index')->with('success', 'Investigation created successfully.');
    }

    public function show($id)
    {
        $investigation = Investigation::with('parameters')->findOrFail($id);
        $labTest = $investigation;
        return view('admin.lab.tests.show', compact('investigation', 'labTest'));
    }

    public function edit($id)
    {
        $investigation = Investigation::with('parameters')->findOrFail($id);
        $labTest = $investigation;
        return view('admin.lab.tests.edit', compact('investigation', 'labTest'));
    }

    public function update(UpdateLabTestRequest $request, $id)
    {
        $investigation = Investigation::findOrFail($id);
        $investigation->update($request->validated());

        if ($request->has('parameters')) {
            $investigation->parameters()->delete();

            foreach ($request->parameters as $index => $paramData) {
                if (!empty($paramData['name'])) {
                    $investigation->parameters()->create([
                        'parameter_name'   => $paramData['name'],
                        'unit'             => $paramData['unit'] ?? null,
                        'data_type'        => 'numeric',
                        'reference_ranges' => !empty($paramData['reference_range']) ? ['range' => $paramData['reference_range']] : null,
                        'display_order'    => $index + 1,
                        'is_active'        => true,
                    ]);
                }
            }
        }

        return redirect()->route('investigations.index')->with('success', 'Investigation updated successfully.');
    }

    public function destroy($id)
    {
        $investigation = Investigation::findOrFail($id);
        $investigation->delete();
        return redirect()->route('investigations.index')->with('success', 'Investigation deleted successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $path     = $request->file('file')->store('imports/investigations', 'local');
        $cacheKey = 'investigation_import_' . auth()->id() . '_' . Str::random(8);

        // Mark as pending immediately so the poller has something to find
        Cache::put($cacheKey, ['status' => 'pending'], now()->addMinutes(30));

        ImportInvestigationsJob::dispatch($path, $cacheKey, auth()->id());

        return redirect()->route('investigations.index')
            ->with('import_pending', true)
            ->with('import_cache_key', $cacheKey);
    }

    /**
     * AJAX endpoint polled by the browser to check background import progress.
     *
     * Returns one of:
     *   { status: 'pending' }                          — job still running
     *   { status: 'done', created, updated, errors[] } — job finished OK
     *   { status: 'failed', message }                  — job threw
     *   { status: 'not_found' }                        — key missing/expired;
     *                                                    client must stop polling
     */
    public function importStatus(Request $request)
    {
        $key = $request->query('key');

        if (!$key) {
            return response()->json(['status' => 'not_found']);
        }

        $result = Cache::get($key);

        // Key doesn't exist — expired or never written.
        // Return 'not_found' so the poller cleans up and stops.
        if ($result === null) {
            return response()->json(['status' => 'not_found']);
        }

        // Job still running
        if ($result['status'] === 'pending') {
            return response()->json(['status' => 'pending']);
        }

        // Job finished (done or failed) — consume the key so it isn't re-read
        Cache::forget($key);

        return response()->json($result);
    }

    // -------------------------------------------------------------------------

    private function toUtf8(string $value): string
    {
        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        $converted = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');

        return mb_check_encoding($converted, 'UTF-8')
            ? $converted
            : mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }

    private function sanitizeString(string $value, string $default = ''): string
    {
        $clean = trim($this->toUtf8($value));
        return $clean !== '' ? $clean : $default;
    }
}
