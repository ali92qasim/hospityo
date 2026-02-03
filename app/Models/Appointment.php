<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_no',
        'patient_id',
        'doctor_id',
        'appointment_datetime',
        'status',
        'reason',
        'notes',
    ];

    protected $casts = [
        'appointment_datetime' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($appointment) {
            $appointment->appointment_no = 'APT' . str_pad(
                (Appointment::max('id') ?? 0) + 1,
                5,
                '0',
                STR_PAD_LEFT
            );
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
