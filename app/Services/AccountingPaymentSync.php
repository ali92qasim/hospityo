<?php

namespace App\Services;

use App\Models\AccountingPayment;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;

class AccountingPaymentSync
{
    private const CASH_CODES = [
        '1100' => 'cash',
        '1110' => 'bank',
    ];

    public static function clearEntry(int $journalEntryId): void
    {
        AccountingPayment::where('journal_entry_id', $journalEntryId)->delete();
    }

    public static function syncEntry(JournalEntry $entry): void
    {
        $entry->loadMissing('lines.account');

        DB::connection('tenant')->transaction(function () use ($entry) {
            self::clearEntry($entry->id);

            $rows = [];
            foreach ($entry->lines as $line) {
                $mapped = self::mapLine($entry, $line);
                if ($mapped !== null) {
                    $rows[] = $mapped;
                }
            }

            if ($rows !== []) {
                AccountingPayment::query()->insert($rows);
            }
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function mapLine(JournalEntry $entry, JournalEntryLine $line): ?array
    {
        $code = $line->account?->code;
        if ($code === null || ! isset(self::CASH_CODES[$code])) {
            return null;
        }

        $debit = (float) $line->debit;
        $credit = (float) $line->credit;

        if ($debit > 0) {
            $type = 'income';
            $amount = $debit;
        } elseif ($credit > 0) {
            $type = 'expense';
            $amount = $credit;
        } else {
            return null;
        }

        $now = now();

        return [
            'amount' => $amount,
            'payment_date' => $entry->entry_date->format('Y-m-d'),
            'payment_type' => $type,
            'payment_method' => self::CASH_CODES[$code],
            'journal_entry_id' => $entry->id,
            'journal_entry_line_id' => $line->id,
            'description' => $entry->description,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
