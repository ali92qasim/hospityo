<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'patient_no',
        'gender',
        'age',
        'phone',
        'marital_status',
        'present_address',
        'permanent_address',
        'emergency_name',
        'emergency_phone',
        'emergency_relation',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($patient) {
            $patient->patient_no = 'P' . str_pad(
                (Patient::max('id') ?? 0) + 1,
                6,
                '0',
                STR_PAD_LEFT
            );
        });
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function prescriptions()
    {
        return $this->hasManyThrough(Prescription::class, Visit::class);
    }

    public function labOrders()
    {
        return $this->hasMany(LabOrder::class);
    }

    public function admissions()
    {
        return $this->hasManyThrough(Admission::class, Visit::class);
    }
}