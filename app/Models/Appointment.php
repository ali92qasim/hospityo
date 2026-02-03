<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    protected static function boot()
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

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}