<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_no',
        'patient_id',
        'doctor_id',
        'visit_type',
        'status',
        'visit_datetime',
    ];

    protected $casts = [
        'visit_datetime' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($visit) {
            $prefix = match($visit->visit_type) {
                'opd' => 'OPD',
                'ipd' => 'IPD',
                'emergency' => 'EMR'
            };
            
            $visit->visit_no = $prefix . str_pad(
                (Visit::where('visit_type', $visit->visit_type)->max('id') ?? 0) + 1,
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

    public function vitalSigns()
    {
        return $this->hasOne(VitalSign::class);
    }

    public function allVitalSigns()
    {
        return $this->hasMany(VitalSign::class)->latest();
    }

    public function consultation()
    {
        return $this->hasOne(Consultation::class);
    }

    public function testOrders()
    {
        return $this->hasMany(TestOrder::class);
    }

    public function admission()
    {
        return $this->hasOne(Admission::class);
    }

    public function triage()
    {
        return $this->hasOne(Triage::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function labOrders()
    {
        return $this->hasMany(LabOrder::class);
    }
}