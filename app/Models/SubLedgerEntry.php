<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class SubLedgerEntry extends Model
{
    use UsesTenantConnection;

    protected $fillable = ['journal_entry_id', 'ledger_type', 'ledger_id', 'debit', 'credit', 'narration'];

    protected $casts = ['debit' => 'decimal:2', 'credit' => 'decimal:2'];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
