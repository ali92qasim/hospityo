<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class DoctorShareSettlement extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'doctor_id',
        'date_from',
        'date_to',
        'item_count',
        'total_settled_amount',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'total_settled_amount' => 'decimal:2',
        'date_from'            => 'date',
        'date_to'              => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function shareItems(): HasMany
    {
        return $this->hasMany(DoctorShareItem::class, 'settlement_id');
    }
}
