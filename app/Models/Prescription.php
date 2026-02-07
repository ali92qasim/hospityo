<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_no',
        'visit_id',
        'patient_id',
        'doctor_id',
        'status',
        'prescribed_date',
        'dispensed_date',
        'total_amount',
        'notes'
    ];

    protected $casts = [
        'prescribed_date' => 'datetime',
        'dispensed_date' => 'datetime',
        'total_amount' => 'decimal:2'
    ];

    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function ($prescription) {
            try {
                $lastId = static::query()->max('id') ?? 0;
                $prescription->prescription_no = 'RX' . str_pad(
                    $lastId + 1,
                    6,
                    '0',
                    STR_PAD_LEFT
                );
            } catch (\Exception $e) {
                \Log::error('Failed to generate prescription number: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }
}