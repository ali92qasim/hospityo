<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Visit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IpdDraftBillService
{
    /**
     * Find the IPD draft bill for a visit without creating one.
     */
    public static function resolveForVisit(Visit $visit): ?Bill
    {
        if ($visit->visit_type !== 'ipd') {
            return null;
        }

        return static::findOrRecoverDraft($visit);
    }

    /**
     * Ensure the IPD visit has a single draft bill for the stay.
     * Idempotent — ward transfers / re-admits must not create another draft.
     */
    public static function ensureForVisit(Visit $visit): ?Bill
    {
        if ($visit->visit_type !== 'ipd') {
            return null;
        }

        $existing = static::findOrRecoverDraft($visit);
        if ($existing) {
            return $existing;
        }

        return DB::connection('tenant')->transaction(function () use ($visit) {
            // Re-check inside transaction in case another request just created it.
            $existing = static::findLinkedDrafts($visit)->first();
            if ($existing) {
                return $existing;
            }

            return Bill::create([
                'patient_id' => $visit->patient_id,
                'visit_id' => $visit->id,
                'bill_number' => static::generateBillNumber(),
                'bill_date' => now()->toDateString(),
                'bill_type' => 'ipd',
                'subtotal' => 0,
                'tax_amount' => 0,
                'discount_type' => 'fixed',
                'discount_percentage' => 0,
                'discount_amount' => 0,
                'total_amount' => 0,
                'paid_amount' => 0,
                'due_amount' => 0,
                'status' => 'draft',
                'notes' => 'IPD draft bill — running charges for admission '.($visit->visit_no ?? ''),
                'created_by' => Auth::id(),
            ]);
        });
    }

    private static function findOrRecoverDraft(Visit $visit): ?Bill
    {
        $linked = static::findLinkedDrafts($visit);

        if ($linked->isNotEmpty()) {
            return static::consolidateDrafts($linked);
        }

        $orphan = static::findOrphanedDraft($visit);
        if ($orphan) {
            $orphan->update(['visit_id' => $visit->id]);

            return $orphan->fresh();
        }

        return null;
    }

    private static function findLinkedDrafts(Visit $visit): Collection
    {
        return Bill::query()
            ->where('visit_id', $visit->id)
            ->where('bill_type', 'ipd')
            ->where('status', 'draft')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Draft bills can lose visit_id when saved from the edit form — recover them.
     */
    private static function findOrphanedDraft(Visit $visit): ?Bill
    {
        $query = Bill::query()
            ->whereNull('visit_id')
            ->where('patient_id', $visit->patient_id)
            ->where('bill_type', 'ipd')
            ->where('status', 'draft');

        $visit->loadMissing('admission');
        if ($visit->admission?->admission_date) {
            $query->where('created_at', '>=', $visit->admission->admission_date);
        }

        return $query->orderByDesc('id')->first();
    }

    /**
     * Keep the draft that actually has charges; remove empty duplicates.
     */
    private static function consolidateDrafts(Collection $drafts): Bill
    {
        if ($drafts->count() === 1) {
            return $drafts->first();
        }

        $keeper = $drafts->sortByDesc(function (Bill $bill) {
            $itemCount = $bill->relationLoaded('billItems')
                ? $bill->billItems->count()
                : $bill->billItems()->count();

            return [$itemCount, (float) $bill->total_amount, $bill->id];
        })->first();

        foreach ($drafts->where('id', '!=', $keeper->id) as $duplicate) {
            $duplicate->billItems()->delete();
            $duplicate->delete();
        }

        return $keeper->fresh(['billItems']);
    }

    private static function generateBillNumber(): string
    {
        $prefix = 'BILL-'.date('Y').'-';

        $lastNumber = Bill::where('bill_number', 'like', $prefix.'%')
            ->selectRaw('MAX(CAST(SUBSTRING(bill_number, ?) AS UNSIGNED)) as max_num', [strlen($prefix) + 1])
            ->value('max_num');

        $nextNumber = ($lastNumber ?? 0) + 1;

        return $prefix.str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
