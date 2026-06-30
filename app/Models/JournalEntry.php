<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class JournalEntry extends Model
{
    use Auditable, UsesTenantConnection;

    protected $fillable = [
        'entry_number', 'entry_date', 'reference_type', 'reference_id',
        'description', 'department_id', 'created_by', 'is_auto', 'entry_type',
    ];

    protected $casts = ['entry_date' => 'date', 'is_auto' => 'boolean'];

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function subLedgerEntries(): HasMany
    {
        return $this->hasMany(SubLedgerEntry::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if entry is balanced (total debits = total credits).
     */
    public function isBalanced(): bool
    {
        return round($this->lines->sum('debit'), 2) === round($this->lines->sum('credit'), 2);
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($entry) {
            if (empty($entry->entry_number)) {
                $entry->entry_number = 'JE-' . date('Ym') . '-' . str_pad(
                    (static::whereYear('created_at', date('Y'))->count() + 1), 5, '0', STR_PAD_LEFT
                );
            }
        });
    }
}
