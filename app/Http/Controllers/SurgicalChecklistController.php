<?php

namespace App\Http\Controllers;

use App\Models\Surgery;
use App\Models\SurgicalChecklist;
use App\Models\SurgicalChecklistItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SurgicalChecklistController extends Controller
{
    /**
     * Show checklist for a surgery. Creates one if it doesn't exist.
     */
    public function show(Surgery $surgery)
    {
        try {
            $checklist = $surgery->surgicalChecklist;

            if (!$checklist) {
                $checklist = $this->initializeChecklist($surgery);
            }

            $checklist->load(['items', 'completedBy']);
            $surgery->load(['patient', 'doctor', 'operationTheatre']);

            $phases = [
                'sign_in'  => $checklist->items->where('phase', 'sign_in')->values(),
                'time_out' => $checklist->items->where('phase', 'time_out')->values(),
                'sign_out' => $checklist->items->where('phase', 'sign_out')->values(),
            ];

            return view('admin.ot.checklist.show', compact('surgery', 'checklist', 'phases'));
        } catch (\Throwable $e) {
            Log::error('[Checklist] Failed to load', ['surgery_id' => $surgery->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to load surgical checklist.');
        }
    }

    /**
     * Toggle a checklist item (AJAX endpoint).
     */
    public function toggleItem(Request $request, SurgicalChecklistItem $item)
    {
        $request->validate([
            'is_checked' => 'required|boolean',
            'notes'      => 'nullable|string|max:500',
        ]);

        try {
            $item->update([
                'is_checked' => $request->is_checked,
                'checked_by' => $request->is_checked ? auth()->id() : null,
                'checked_at' => $request->is_checked ? now() : null,
                'notes'      => $request->notes,
            ]);

            // Update phase completion status
            $checklist = $item->checklist;
            $this->updateChecklistStatus($checklist);

            return response()->json([
                'success' => true,
                'item'    => $item->fresh(),
                'status'  => $checklist->fresh()->status,
            ]);
        } catch (\Throwable $e) {
            Log::error('[Checklist] Failed to toggle item', ['item_id' => $item->id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to update item.'], 500);
        }
    }

    /**
     * Complete a phase manually (marks phase timestamp).
     */
    public function completePhase(Request $request, SurgicalChecklist $checklist)
    {
        $request->validate([
            'phase' => 'required|in:sign_in,time_out,sign_out',
        ]);

        $phase = $request->phase;

        // Ensure all items in this phase are checked
        $unchecked = $checklist->items()->where('phase', $phase)->where('is_checked', false)->count();
        if ($unchecked > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot complete phase — {$unchecked} item(s) still unchecked.",
            ], 422);
        }

        try {
            $timestampField = $phase . '_completed_at';
            $checklist->update([
                $timestampField => now(),
                'completed_by'  => auth()->id(),
            ]);

            $this->updateChecklistStatus($checklist);

            return response()->json([
                'success' => true,
                'status'  => $checklist->fresh()->status,
            ]);
        } catch (\Throwable $e) {
            Log::error('[Checklist] Failed to complete phase', ['id' => $checklist->id, 'phase' => $phase, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to complete phase.'], 500);
        }
    }

    // ── Private Helpers ──

    /**
     * Initialize a checklist with WHO default items for a surgery.
     */
    private function initializeChecklist(Surgery $surgery): SurgicalChecklist
    {
        return DB::connection('tenant')->transaction(function () use ($surgery) {
            $checklist = SurgicalChecklist::create([
                'surgery_id' => $surgery->id,
                'status'     => 'incomplete',
            ]);

            $sortOrder = 0;
            foreach (SurgicalChecklist::DEFAULT_ITEMS as $phase => $items) {
                foreach ($items as $item) {
                    SurgicalChecklistItem::create([
                        'surgical_checklist_id' => $checklist->id,
                        'phase'                 => $phase,
                        'item_key'              => $item['item_key'],
                        'label'                 => $item['label'],
                        'is_checked'            => false,
                        'sort_order'            => $sortOrder++,
                    ]);
                }
            }

            return $checklist;
        });
    }

    /**
     * Update the overall checklist status based on phase completions.
     */
    private function updateChecklistStatus(SurgicalChecklist $checklist): void
    {
        $signInDone  = $checklist->isSignInComplete() && $checklist->sign_in_completed_at;
        $timeOutDone = $checklist->isTimeOutComplete() && $checklist->time_out_completed_at;
        $signOutDone = $checklist->isSignOutComplete() && $checklist->sign_out_completed_at;

        if ($signOutDone && $timeOutDone && $signInDone) {
            $checklist->update(['status' => 'completed']);
        } elseif ($timeOutDone && $signInDone) {
            $checklist->update(['status' => 'time_out_done']);
        } elseif ($signInDone) {
            $checklist->update(['status' => 'sign_in_done']);
        } else {
            $checklist->update(['status' => 'incomplete']);
        }
    }
}
