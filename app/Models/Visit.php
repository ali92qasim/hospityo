<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    protected static function boot(): void
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

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function vitalSigns(): HasOne
    {
        return $this->hasOne(VitalSign::class);
    }

    public function allVitalSigns(): HasMany
    {
        return $this->hasMany(VitalSign::class)->latest();
    }

    public function consultation(): HasOne
    {
        return $this->hasOne(Consultation::class);
    }

    public function testOrders(): HasMany
    {
        return $this->hasMany(TestOrder::class);
    }

    public function admission(): HasOne
    {
        return $this->hasOne(Admission::class);
    }

    public function triage(): HasOne
    {
        return $this->hasOne(Triage::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function labOrders(): HasMany
    {
        return $this->hasMany(InvestigationOrder::class);
    }

    public function investigationOrders(): HasMany
    {
        return $this->hasMany(InvestigationOrder::class);
    }
}