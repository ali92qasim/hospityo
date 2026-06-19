<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class PreAnaesthesiaCheckup extends Model
{
    use Auditable, UsesTenantConnection;

    protected $fillable = [
        'surgery_id', 'patient_id', 'anaesthetist_id', 'requested_by',
        'asa_grade', 'medical_history', 'current_medications', 'allergies',
        'airway_assessment', 'mallampati_class', 'cardiovascular_status',
        'respiratory_status', 'renal_hepatic_status',
        'blood_pressure', 'heart_rate', 'spo2', 'weight_kg',
        'investigations_reviewed',
        'proposed_anaesthesia_type', 'special_precautions', 'fasting_instructions', 'premedication',
        'status', 'clearance_notes', 'cleared_at',
    ];

    protected $casts = [
        'cleared_at' => 'datetime',
    ];

    // ── Relationships ──

    public function surgery(): BelongsTo
    {
        return $this->belongsTo(Surgery::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function anaesthetist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anaesthetist_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    // ── Helpers ──

    public function isCleared(): bool
    {
        return $this->status === 'cleared';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
