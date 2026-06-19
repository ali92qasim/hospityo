<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class AnaesthesiaRecord extends Model
{
    use Auditable, UsesTenantConnection;

    protected $fillable = [
        'surgery_id', 'anaesthetist_id', 'anaesthesia_type',
        'airway_management', 'ett_size', 'induction_agent', 'induction_dose',
        'maintenance_agent', 'muscle_relaxant', 'reversal_agent',
        'regional_technique', 'iv_fluids',
        'estimated_blood_loss_ml', 'urine_output_ml',
        'intra_op_medications', 'intra_op_events',
        'induction_time', 'intubation_time', 'extubation_time',
        'recovery_status', 'post_op_instructions', 'pain_management_plan',
    ];

    protected $casts = [
        'induction_time'   => 'datetime',
        'intubation_time'  => 'datetime',
        'extubation_time'  => 'datetime',
    ];

    public function surgery(): BelongsTo
    {
        return $this->belongsTo(Surgery::class);
    }

    public function anaesthetist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anaesthetist_id');
    }
}
