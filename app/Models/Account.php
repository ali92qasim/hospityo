<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Account extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'code', 'name', 'type', 'parent_id', 'description', 'is_system', 'is_active',
    ];

    protected $casts = ['is_system' => 'boolean', 'is_active' => 'boolean'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeOfType(Builder $q, string $type): Builder
    {
        return $q->where('type', $type);
    }

    /**
     * Get account balance (debit - credit for assets/expenses, credit - debit for liabilities/revenue/equity).
     */
    public function getBalance(?string $from = null, ?string $to = null): float
    {
        $query = $this->journalLines();
        if ($from) $query->whereHas('journalEntry', fn($q) => $q->where('entry_date', '>=', $from));
        if ($to) $query->whereHas('journalEntry', fn($q) => $q->where('entry_date', '<=', $to));

        $debits = (float) $query->sum('debit');
        $credits = (float) (clone $query)->sum('credit');

        return in_array($this->type, ['asset', 'expense'])
            ? $debits - $credits
            : $credits - $debits;
    }
}
