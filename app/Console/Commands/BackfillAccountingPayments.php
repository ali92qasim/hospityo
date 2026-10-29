<?php

namespace App\Console\Commands;

use App\Models\AccountingPayment;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\AccountingPaymentSync;
use Illuminate\Console\Command;

/**
 * Backfills accounting_payments from historical cash/bank journal lines.
 *
 * Uses the same 1100/1110 → cash/bank and debit/credit → income/expense
 * mapping as AccountingPaymentSync. Idempotent by journal_entry_line_id.
 * With --dry-run nothing is written.
 */
class BackfillAccountingPayments extends Command
{
    protected $signature = 'accounting:backfill-payments {--dry-run : Report rows that would be inserted without writing}';

    protected $description = 'Backfill accounting_payments from existing cash/bank journal entry lines';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $inserted = 0;
        $skipped = 0;

        JournalEntryLine::query()
            ->whereHas('account', fn ($q) => $q->whereIn('code', ['1100', '1110']))
            ->whereHas('journalEntry', fn ($q) => $q->where('entry_type', '!=', 'superseded'))
            ->with(['account', 'journalEntry'])
            ->orderBy('id')
            ->chunkById(200, function ($lines) use ($dryRun, &$inserted, &$skipped) {
                $existingLineIds = AccountingPayment::query()
                    ->whereIn('journal_entry_line_id', $lines->pluck('id'))
                    ->pluck('journal_entry_line_id')
                    ->all();
                $existingSet = array_flip($existingLineIds);

                $missingByEntry = [];
                foreach ($lines as $line) {
                    if (isset($existingSet[$line->id])) {
                        $skipped++;

                        continue;
                    }

                    $missingByEntry[$line->journal_entry_id][] = $line;
                }

                if ($missingByEntry === []) {
                    return;
                }

                if ($dryRun) {
                    foreach ($missingByEntry as $entryLines) {
                        $inserted += count($entryLines);
                    }

                    return;
                }

                foreach (array_keys($missingByEntry) as $entryId) {
                    $entry = JournalEntry::with('lines.account')->find($entryId);
                    if ($entry === null) {
                        continue;
                    }

                    AccountingPaymentSync::syncEntry($entry);
                    $inserted += count($missingByEntry[$entryId]);
                }
            });

        if ($dryRun) {
            $this->info("[dry-run] Would insert {$inserted} accounting payment(s) ({$skipped} already present).");
        } else {
            $this->info("Inserted {$inserted} accounting payment(s) ({$skipped} already present).");
        }

        return self::SUCCESS;
    }
}
