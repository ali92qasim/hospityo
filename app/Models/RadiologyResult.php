<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiologyResult extends Model
{
    protected $fillable = [
        'investigation_order_id',
        'report_text',
        'impression',
        'file_path',
        'status',
        'radiologist_id',
        'reported_at'
    ];

    protected $casts = [
        'reported_at' => 'datetime'
    ];

    public function investigationOrder(): BelongsTo
    {
        return $this->belongsTo(InvestigationOrder::class);
    }

    public function radiologist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'radiologist_id');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isFinal(): bool
    {
        return $this->status === 'final';
    }

    public function isAmended(): bool
    {
        return $this->status === 'amended';
    }
}
