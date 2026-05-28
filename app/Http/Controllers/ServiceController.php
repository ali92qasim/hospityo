<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Jobs\Tenant\ImportServicesJob;
use App\Models\Department;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::with('department')->paginate(10);
        return view('admin.services.index', compact('services'));
    }

    public function create()
    {
        $departments = Department::all();
        return view('admin.services.create', compact('departments'));
    }

    public function store(StoreServiceRequest $request)
    {
        Service::create($request->all());

        return redirect()->route('services.index')->with('success', 'Service created successfully');
    }

    public function edit(Service $service)
    {
        $departments = Department::all();
        return view('admin.services.edit', compact('service', 'departments'));
    }

    public function update(UpdateServiceRequest $request, Service $service)
    {
        $service->update($request->all());

        return redirect()->route('services.index')->with('success', 'Service updated successfully');
    }

    public function destroy(Service $service)
    {
        $service->delete();
        return redirect()->route('services.index')->with('success', 'Service deleted successfully');
    }

    // -------------------------------------------------------------------------
    // Bulk Import
    // -------------------------------------------------------------------------

    /**
     * Accept a CSV or Excel file, store it, and dispatch a deferred import job.
     * The job runs at the end of this HTTP request (no queue worker needed).
     * The browser polls importStatus() to get the result.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:csv,txt,xlsx,xls',
                'max:10240', // 10 MB
            ],
        ], [
            'file.mimes' => 'Only CSV and Excel files (.csv, .xlsx, .xls) are accepted.',
            'file.max'   => 'The file must not exceed 10 MB.',
        ]);

        try {
            $path     = $request->file('file')->store('imports/services', 'local');
            $cacheKey = 'service_import_' . auth()->id() . '_' . Str::random(8);

            // Mark as pending immediately so the poller has something to find
            Cache::put($cacheKey, ['status' => 'pending'], now()->addMinutes(30));

            // Dispatch on the 'deferred' connection — runs after the response is sent,
            // in the same PHP process. No artisan queue:work needed.
            ImportServicesJob::dispatch($path, $cacheKey, auth()->id())
                ->onConnection('deferred');

        } catch (\Throwable $e) {
            Log::error('[ServiceImport] Failed to dispatch import job', [
                'user_id' => auth()->id(),
                'error'   => $e->getMessage(),
            ]);
            return back()->with('error', 'Failed to start the import. Please try again.');
        }

        return redirect()->route('services.index')
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
     *   { status: 'not_found' }                        — key missing/expired
     */
    public function importStatus(Request $request)
    {
        $key = $request->query('key');

        if (!$key) {
            return response()->json(['status' => 'not_found']);
        }

        $result = Cache::get($key);

        if ($result === null) {
            return response()->json(['status' => 'not_found']);
        }

        if ($result['status'] === 'pending') {
            return response()->json(['status' => 'pending']);
        }

        // Consume the key — don't let it be re-read
        Cache::forget($key);

        return response()->json($result);
    }
}
