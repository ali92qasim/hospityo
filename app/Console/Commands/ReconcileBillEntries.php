<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\AccountingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Models\Tenant;

class ReconcileBillEntries extends Command
{
    protected $signature = 'accounting:reconcile-bills
                            {--tenant= : Specific tenant ID (omit to run on all)}
                            {--dry-run : Show mismatches without posting corrections}
                            {--include-orphans : Also reverse journal entries for deleted bills}';

    protected $description = 'Find and fix bill journal entries that are out of sync with current bill amounts, and optionally reverse orphaned entries for deleted bills';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $tenantId = $this->option('tenant');
        $includeOrphans = $this->option('include-orphans');

        $tenants = $tenantId
            ? Tenant::where('id', $tenantId)->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->error('No tenants found.');
            return self::FAILURE;
        }

        $totalMismatches = 0;
        $totalFixed = 0;
        $totalOrphans = 0;
        $totalOrphansFixed = 0;

        foreach ($tenants as $tenant) {
            $tenant->makeCurrent();
            $this->info("Processing tenant: {$tenant->name} (ID: {$tenant->id})");

            // Track bill IDs fixed in Part 1 so Part 3 skips them
            $fixedBillIds = collect();

            // ── Part 1: Fix mismatched bill entries ──────────────────────────
            $mismatches = $this->findMismatchedBills();

            if ($mismatches->isEmpty()) {
                $this->line("  No mismatches found.");
            } else {
                $this->warn("  Found {$mismatches->count()} bill(s) with stale journal entries.");
                $totalMismatches += $mismatches->count();

                foreach ($mismatches as $data) {
                    $bill = Bill::find($data->bill_id);
                    if (!$bill) continue;

                    $this->line("  Bill #{$bill->bill_number}: ledger receivable={$data->journal_receivable}, bill total={$bill->total_amount}");

                    if ($dryRun) {
                        $fixedBillIds->push($bill->id);
                        continue;
                    }

                    DB::connection('tenant')->transaction(function () use ($bill) {
                        AccountingService::reverseAndRepostBillEntry($bill, 'retroactive reconciliation');

                        $overpayment = round((float) $bill->paid_amount - (float) $bill->total_amount, 2);
                        if ($overpayment > 0) {
                            $bill->due_amount = 0;
                            $bill->status = 'paid';
                            $bill->save();

                            AccountingService::postOverpaymentAdjustment($bill, $overpayment);
                        } else {
                            $paid = (float) $bill->paid_amount;
                            $total = (float) $bill->total_amount;
                            $bill->status = $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'pending');
                            $bill->due_amount = max(0, $total - $paid);
                            $bill->save();
                        }
                    });

                    $fixedBillIds->push($bill->id);
                    $totalFixed++;
                    $this->info("    ✓ Corrected");
                }
            }

            // ── Part 2: Reverse orphaned entries for deleted bills ───────────
            if ($includeOrphans) {
                $orphans = $this->findOrphanedEntries();

                if ($orphans->isEmpty()) {
                    $this->line("  No orphaned entries found.");
                } else {
                    $this->warn("  Found {$orphans->count()} orphaned journal entry(s) for deleted bills.");
                    $totalOrphans += $orphans->count();

                    foreach ($orphans as $orphan) {
                        $this->line("  JE#{$orphan->id} (Bill#{$orphan->reference_id}): {$orphan->description}");

                        if ($dryRun) continue;

                        $entry = JournalEntry::find($orphan->id);
                        if (!$entry) continue;

                        // Check if already reversed
                        $alreadyReversed = JournalEntry::where('description', 'like', "REVERSAL — " . substr($entry->description, 0, 50) . "%")
                            ->where('reference_type', $entry->reference_type)
                            ->where('reference_id', $entry->reference_id)
                            ->exists();

                        if ($alreadyReversed) {
                            $this->line("    Already reversed, skipping.");
                            continue;
                        }

                        $reversal = AccountingService::reverseEntry($entry, 'bill_deleted_orphaned_entry');

                        if ($reversal) {
                            $totalOrphansFixed++;
                            $this->info("    ✓ Reversed → JE#{$reversal->id}");
                        } else {
                            $this->error("    ✗ Reversal failed");
                        }
                    }
                }
            }

            // ── Part 3: Fix discount mismatches ─────────────────────────────
            // Bills where the 5200 (Discounts Given) journal net doesn't match
            // bills.discount_amount. This catches cases where discount was added/removed
            // but the receivable (1200) total still matches (masked mismatch).
            // Skip bills already fixed in Part 1 (they've already been reverse-and-reposted).
            $discountMismatches = $this->findDiscountMismatches()
                ->filter(fn($d) => !$fixedBillIds->contains($d->bill_id));

            if ($discountMismatches->isEmpty()) {
                $this->line("  No discount mismatches found.");
            } else {
                $this->warn("  Found {$discountMismatches->count()} bill(s) with stale discount journal entries.");
                $totalMismatches += $discountMismatches->count();

                foreach ($discountMismatches as $data) {
                    $bill = Bill::find($data->bill_id);
                    if (!$bill) continue;

                    $this->line("  Bill #{$bill->bill_number}: journal discount_net={$data->journal_discount}, bill discount={$bill->discount_amount}");

                    if ($dryRun) continue;

                    DB::connection('tenant')->transaction(function () use ($bill) {
                        // Full reverse-and-repost handles all lines (receivable, revenue, discount, tax)
                        AccountingService::reverseAndRepostBillEntry($bill, 'discount reconciliation');

                        // Re-evaluate status
                        $overpayment = round((float) $bill->paid_amount - (float) $bill->total_amount, 2);
                        if ($overpayment > 0) {
                            $bill->due_amount = 0;
                            $bill->status = 'paid';
                            $bill->save();
                            AccountingService::postOverpaymentAdjustment($bill, $overpayment);
                        } else {
                            $paid = (float) $bill->paid_amount;
                            $total = (float) $bill->total_amount;
                            $bill->status = $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'pending');
                            $bill->due_amount = max(0, $total - $paid);
                            $bill->save();
                        }
                    });

                    $totalFixed++;
                    $this->info("    ✓ Corrected");
                }
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->warn("DRY RUN: {$totalMismatches} mismatched bill(s) found.");
            if ($includeOrphans) {
                $this->warn("DRY RUN: {$totalOrphans} orphaned entry(s) found for deleted bills.");
            }
            $this->line("Run without --dry-run to apply corrections.");
        } else {
            $this->info("Done. Fixed {$totalFixed} / {$totalMismatches} mismatched bill(s).");
            if ($includeOrphans) {
                $this->info("Reversed {$totalOrphansFixed} / {$totalOrphans} orphaned entry(s).");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Find bills whose journal entry receivable DR amount differs from current bill total.
     */
    private function findMismatchedBills()
    {
        $receivableAccountId = DB::connection('tenant')
            ->table('accounts')
            ->where('code', '1200')
            ->value('id');

        if (!$receivableAccountId) {
            return collect();
        }

        return DB::connection('tenant')
            ->table('bills as b')
            ->join('journal_entries as je', function ($join) {
                $join->on('je.reference_id', '=', 'b.id')
                    ->where('je.reference_type', '=', 'Bill')
                    ->where('je.description', 'not like', 'REVERSAL%')
                    ->where('je.description', 'not like', 'Overpayment%');
            })
            ->join('journal_entry_lines as jel', function ($join) use ($receivableAccountId) {
                $join->on('jel.journal_entry_id', '=', 'je.id')
                    ->where('jel.account_id', '=', $receivableAccountId)
                    ->where('jel.debit', '>', 0);
            })
            ->select(
                'b.id as bill_id',
                'b.total_amount',
                DB::raw('jel.debit as journal_receivable')
            )
            ->whereRaw('ROUND(b.total_amount, 2) != ROUND(jel.debit, 2)')
            ->get();
    }

    /**
     * Find journal entries that reference bills which no longer exist in the bills table.
     * Only returns non-reversal entries (we don't want to reverse a reversal).
     */
    private function findOrphanedEntries()
    {
        return DB::connection('tenant')
            ->table('journal_entries as je')
            ->leftJoin('bills as b', function ($join) {
                $join->on('b.id', '=', 'je.reference_id');
            })
            ->where('je.reference_type', 'Bill')
            ->where('je.description', 'not like', 'REVERSAL%')
            ->where('je.description', 'not like', 'Overpayment%')
            ->whereNull('b.id')
            ->select('je.id', 'je.reference_id', 'je.description', 'je.entry_date')
            ->orderBy('je.id')
            ->get();
    }

    /**
     * Find bills where the net discount in journal (5200 debits - credits) doesn't match
     * the bill's current discount_amount. This catches edits where discount changed but
     * receivable total stayed the same (e.g., discount removed and item price adjusted).
     */
    private function findDiscountMismatches()
    {
        $discountAccountId = DB::connection('tenant')
            ->table('accounts')
            ->where('code', '5200')
            ->value('id');

        if (!$discountAccountId) {
            return collect();
        }

        // Get all bills and compare their discount_amount with the journal net on 5200
        $bills = DB::connection('tenant')->table('bills')->get();
        $mismatches = collect();

        foreach ($bills as $bill) {
            $totalDebit = (float) DB::connection('tenant')
                ->table('journal_entry_lines as jel')
                ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
                ->where('je.reference_type', 'Bill')
                ->where('je.reference_id', $bill->id)
                ->where('jel.account_id', $discountAccountId)
                ->sum('jel.debit');

            $totalCredit = (float) DB::connection('tenant')
                ->table('journal_entry_lines as jel')
                ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
                ->where('je.reference_type', 'Bill')
                ->where('je.reference_id', $bill->id)
                ->where('jel.account_id', $discountAccountId)
                ->sum('jel.credit');

            $journalNet = round($totalDebit - $totalCredit, 2);
            $billDiscount = round((float) $bill->discount_amount, 2);

            if ($journalNet != $billDiscount) {
                $mismatches->push((object) [
                    'bill_id' => $bill->id,
                    'journal_discount' => $journalNet,
                    'bill_discount' => $billDiscount,
                ]);
            }
        }

        return $mismatches;
    }
}
