<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($prescription) {
            $prescription->prescription_no = 'RX' . str_pad(
                (Prescription::max('id') ?? 0) + 1,
                6,
                '0',
                STR_PAD_LEFT
            );
        });
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function items()
    {
        return $this->hasMany(PrescriptionItem::class);
    }
}