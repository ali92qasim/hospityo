<?php

namespace App\Http\Controllers;

use App\Models\OperationTheatre;
use App\Models\OtConsumable;
use App\Models\SterilizationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SterilizationController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = SterilizationLog::with(['theatre', 'consumable', 'performedByUser'])
                ->latest();

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('method')) {
                $query->where('method', $request->method);
            }
            if ($request->filled('target_type')) {
                $query->where('target_type', $request->target_type);
            }

            $logs = $query->paginate(20)->withQueryString();
            $pendingCount = SterilizationLog::where('status', 'scheduled')->count();

            return view('admin.ot.sterilization.index', compact('logs', 'pendingCount'));
        } catch (\Throwable $e) {
            Log::error('[Sterilization] Index failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to load sterilization logs.');
        }
    }

    public function create()
    {
        try {
            $theatres = OperationTheatre::active()->orderBy('name')->get();
            $instruments = OtConsumable::active()
                ->whereIn('category', ['instrument'])
                ->orderBy('name')->get();

            return view('admin.ot.sterilization.create', compact('theatres', 'instruments'));
        } catch (\Throwable $e) {
            Log::error('[Sterilization] Create form failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to load form.');
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'target_type'          => 'required|in:theatre,instrument_set,individual_instrument',
            'operation_theatre_id' => 'nullable|exists:tenant.operation_theatres,id',
            'ot_consumable_id'     => 'nullable|exists:tenant.ot_consumables,id',
            'instrument_set_name'  => 'nullable|string|max:255',
            'method'               => 'required|in:autoclave,chemical,dry_heat,ethylene_oxide,plasma',
            'cycle_number'         => 'nullable|string|max:50',
            'temperature'          => 'nullable|integer|min:0|max:300',
            'duration_minutes'     => 'nullable|integer|min:1|max:600',
            'scheduled_at'         => 'nullable|date',
            'notes'                => 'nullable|string|max:2000',
        ]);

        try {
            SterilizationLog::create(array_merge($validated, [
                'status'     => $validated['scheduled_at'] ? 'scheduled' : 'in_progress',
                'started_at' => $validated['scheduled_at'] ? null : now(),
                'created_by' => auth()->id(),
            ]));
        } catch (\Throwable $e) {
            Log::error('[Sterilization] Store failed', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to create sterilization log.');
        }

        return redirect()->route('ot.sterilization.index')->with('success', 'Sterilization log created.');
    }

    public function show(SterilizationLog $sterilization)
    {
        try {
            $sterilization->load(['theatre', 'consumable', 'performedByUser', 'verifiedByUser', 'createdByUser']);
            return view('admin.ot.sterilization.show', compact('sterilization'));
        } catch (\Throwable $e) {
            Log::error('[Sterilization] Show failed', ['id' => $sterilization->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to load sterilization details.');
        }
    }

    /**
     * Start a scheduled sterilization cycle.
     */
    public function start(SterilizationLog $sterilization)
    {
        if ($sterilization->status !== 'scheduled') {
            return back()->with('error', 'Only scheduled sterilizations can be started.');
        }

        try {
            $sterilization->update([
                'status'       => 'in_progress',
                'started_at'   => now(),
                'performed_by' => auth()->id(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[Sterilization] Start failed', ['id' => $sterilization->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to start sterilization.');
        }

        return back()->with('success', 'Sterilization cycle started.');
    }

    /**
     * Complete a sterilization with indicator results.
     */
    public function complete(Request $request, SterilizationLog $sterilization)
    {
        if ($sterilization->status !== 'in_progress') {
            return back()->with('error', 'Only in-progress sterilizations can be completed.');
        }

        $validated = $request->validate([
            'chemical_indicator_result'   => 'required|in:pass,fail',
            'biological_indicator_result' => 'required|in:pass,fail,pending',
            'notes'                       => 'nullable|string|max:2000',
        ]);

        try {
            $hasFailed = $validated['chemical_indicator_result'] === 'fail'
                      || $validated['biological_indicator_result'] === 'fail';

            $sterilization->update([
                'status'                       => $hasFailed ? 'failed' : 'completed',
                'completed_at'                 => now(),
                'chemical_indicator_result'     => $validated['chemical_indicator_result'],
                'biological_indicator_result'   => $validated['biological_indicator_result'],
                'notes'                        => $validated['notes'] ?? $sterilization->notes,
                'performed_by'                 => $sterilization->performed_by ?? auth()->id(),
                'failure_reason'               => $hasFailed ? 'Indicator test failed — re-sterilization required' : null,
            ]);
        } catch (\Throwable $e) {
            Log::error('[Sterilization] Complete failed', ['id' => $sterilization->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to complete sterilization.');
        }

        $msg = $hasFailed
            ? 'Sterilization FAILED — indicators did not pass. Re-sterilization required.'
            : 'Sterilization completed successfully.';

        return back()->with($hasFailed ? 'error' : 'success', $msg);
    }

    /**
     * Verify a completed sterilization (second person sign-off).
     */
    public function verify(SterilizationLog $sterilization)
    {
        if ($sterilization->status !== 'completed') {
            return back()->with('error', 'Only completed sterilizations can be verified.');
        }

        if ($sterilization->performed_by === auth()->id()) {
            return back()->with('error', 'The person who performed sterilization cannot verify it (dual sign-off required).');
        }

        try {
            $sterilization->update([
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[Sterilization] Verify failed', ['id' => $sterilization->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to verify sterilization.');
        }

        return back()->with('success', 'Sterilization verified (dual sign-off complete).');
    }

    /**
     * Mark as failed with reason.
     */
    public function fail(Request $request, SterilizationLog $sterilization)
    {
        if (in_array($sterilization->status, ['completed', 'failed'])) {
            return back()->with('error', 'This log cannot be marked as failed.');
        }

        $request->validate([
            'failure_reason' => 'required|string|max:1000',
        ]);

        try {
            $sterilization->update([
                'status'         => 'failed',
                'failure_reason' => $request->failure_reason,
                'completed_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[Sterilization] Fail mark failed', ['id' => $sterilization->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to update sterilization status.');
        }

        return back()->with('success', 'Sterilization marked as failed.');
    }
}
