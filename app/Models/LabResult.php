<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabResult extends Model
{
    protected $fillable = [
        'lab_order_id', 'results', 'flags', 'interpretation', 'comments', 'status', 
        'technician_id', 'pathologist_id', 'tested_at', 'verified_at', 'reported_at'
    ];

    protected $casts = [
        'results' => 'array',
        'flags' => 'array',
        'tested_at' => 'datetime',
        'verified_at' => 'datetime',
        'reported_at' => 'datetime'
    ];

    public function labOrder(): BelongsTo
    {
        return $this->belongsTo(LabOrder::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function pathologist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pathologist_id');
    }

    public function resultItems(): HasMany
    {
        return $this->hasMany(LabResultItem::class);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}