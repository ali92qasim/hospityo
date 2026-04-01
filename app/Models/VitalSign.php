<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class VitalSign extends Model
{
    use HasFactory, Auditable, UsesTenantConnection;

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
