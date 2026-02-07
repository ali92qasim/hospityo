<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

class LabOrder extends Model
{
    protected $fillable = [
        'order_number', 'patient_id', 'visit_id', 'doctor_id', 'lab_test_id', 'quantity',
        'priority', 'status', 'test_location', 'ordered_at', 'sample_collected_at', 'completed_at',
        'clinical_notes', 'special_instructions'
    ];

    protected $casts = [
        'ordered_at' => 'datetime',
        'sample_collected_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function ($order) {
            try {
                $lastId = static::query()->max('id') ?? 0;
                $order->order_number = 'LAB' . str_pad(
                    $lastId + 1,
                    6,
                    '0',
                    STR_PAD_LEFT
                );
            } catch (\Exception $e) {
                \Log::error('Failed to generate lab order number: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTest::class);
    }

    public function sample(): HasOne
    {
        return $this->hasOne(LabSample::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(LabResult::class);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }
}