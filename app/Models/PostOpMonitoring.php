<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class PostOpMonitoring extends Model
{
    use UsesTenantConnection;

    protected $table = 'post_op_monitoring';

    protected $fillable = [
        'surgery_id', 'recorded_at', 'phase',
        'consciousness_level', 'blood_pressure', 'heart_rate', 'spo2',
        'respiratory_rate', 'temperature', 'pain_score', 'nausea_vomiting',
        'wound_status', 'drain_output', 'iv_fluids_given',
        'medications_given', 'notes', 'recorded_by',
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
