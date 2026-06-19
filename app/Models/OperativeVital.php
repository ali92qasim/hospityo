<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class OperativeVital extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'surgery_id', 'recorded_at',
        'blood_pressure_systolic', 'blood_pressure_diastolic',
        'heart_rate', 'spo2', 'etco2', 'respiratory_rate',
        'temperature', 'mac_value', 'fio2', 'notes', 'recorded_by',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function surgery(): BelongsTo
    {
        return $this->belongsTo(Surgery::class);
    }

    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
