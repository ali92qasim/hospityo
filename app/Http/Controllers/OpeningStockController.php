<?php

namespace App\Http\Controllers;

use App\Jobs\Tenant\ImportOpeningStockJob;
use App\Models\Tenant;
use App\Services\OpeningStockService;
use App\Support\BackgroundQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OpeningStockController extends Controller
{
    public function index()
    {
        $status = OpeningStockService::status();

        return view('admin.inventory.opening-stock', [
            'locked' => $status['locked'],
            'importedAt' => OpeningStockService::importedAtFormatted(),
            'importedBy' => $status['imported_by'],
            'batchCount' => $status['batch_count'],
        ]);
    }

    public function import(Request $request)
    {
        if (OpeningStockService::isLocked()) {
            return redirect()
                ->route('inventory.opening-stock')
                ->with('error', 'Opening stock has already been imported. This action can only be performed once.');
        }

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

            $path = $request->file('file')->store('imports/opening-stock', 'local');
            $cacheKey = 'opening_stock_import_' . auth()->id() . '_' . Str::random(8);

            Cache::put($cacheKey, ['status' => 'pending'], now()->addMinutes(30));

            ImportOpeningStockJob::dispatch($path, $cacheKey, auth()->id(), $tenant->id);

            BackgroundQueue::processNextJob();
        } catch (\Throwable $e) {
            Log::error('[OpeningStockImport] Failed to dispatch import job', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to start the import. Please try again.');
        }

        return redirect()
            ->route('inventory.opening-stock')
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
