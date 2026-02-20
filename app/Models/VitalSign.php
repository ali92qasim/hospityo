<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VitalSign extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id',
        'blood_pressure',
        'temperature',
        'pulse_rate',
        'spo2',
        'bsr',
        'weight',
        'height',
        'notes',
        'recorded_by'
    ];

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}