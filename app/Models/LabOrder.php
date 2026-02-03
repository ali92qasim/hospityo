<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabOrder extends Model
{
    protected $fillable = [
        'order_number', 'patient_id', 'visit_id', 'doctor_id', 'lab_test_id',
        'priority', 'status', 'test_location', 'ordered_at', 'sample_collected_at', 'completed_at',
        'clinical_notes', 'special_instructions'
    ];

    protected $casts = [
        'ordered_at' => 'datetime',
        'sample_collected_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($order) {
            $order->order_number = 'LAB' . str_pad(
                (LabOrder::max('id') ?? 0) + 1,
                6,
                '0',
                STR_PAD_LEFT
            );
        });
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function labTest()
    {
        return $this->belongsTo(LabTest::class);
    }

    public function sample()
    {
        return $this->hasOne(LabSample::class);
    }

    public function result()
    {
        return $this->hasOne(LabResult::class);
    }

    public function resultItems()
    {
        return $this->hasMany(LabResultItem::class);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }
}