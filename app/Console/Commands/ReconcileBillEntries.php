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
                            {--dry-run : Show mismatches without posting corrections}';

    protected $description = 'Find and fix bill journal entries that are out of sync with current bill amounts (e.g., after discount edits)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $tenantId = $this->option('tenant');

        $tenants = $tenantId
            ? Tenant::where('id', $tenantId)->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->error('No tenants found.');
            return self::FAILURE;
        }

        $totalMismatches = 0;
        $totalFixed = 0;

        foreach ($tenants as $tenant) {
            $tenant->makeCurrent();
            $this->info("Processing tenant: {$tenant->name} (ID: {$tenant->id})");

            $mismatches = $this->findMismatchedBills();

            if ($mismatches->isEmpty()) {
                $this->line("  No mismatches found.");
                continue;
            }

            $this->warn("  Found {$mismatches->count()} bill(s) with stale journal entries.");
            $totalMismatches += $mismatches->count();

            foreach ($mismatches as $data) {
                $bill = Bill::find($data->bill_id);
                if (!$bill) continue;

                $this->line("  Bill #{$bill->bill_number}: ledger receivable={$data->journal_receivable}, bill total={$bill->total_amount}");

                if ($dryRun) continue;

                DB::connection('tenant')->transaction(function () use ($bill) {
                    // Reverse and repost the bill entry
                    AccountingService::reverseAndRepostBillEntry($bill, 'retroactive reconciliation');

                    // Handle overpayment if paid > total
                    $overpayment = round((float) $bill->paid_amount - (float) $bill->total_amount, 2);
                    if ($overpayment > 0) {
                        $bill->due_amount = 0;
                        $bill->status = 'paid';
                        $bill->save();

                        AccountingService::postOverpaymentAdjustment($bill, $overpayment);
                    } else {
                        // Re-evaluate status
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

        $this->newLine();
        if ($dryRun) {
            $this->warn("DRY RUN: {$totalMismatches} mismatched bill(s) found. No changes made.");
            $this->line("Run without --dry-run to apply corrections.");
        } else {
            $this->info("Done. Fixed {$totalFixed} / {$totalMismatches} mismatched bill(s).");
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

        // Find the most recent non-reversal bill entry per bill and compare its
        // receivable debit with the current bill total_amount.
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
}
