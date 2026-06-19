<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class SurgicalChecklist extends Model
{
    use Auditable, UsesTenantConnection;

    protected $fillable = [
        'surgery_id', 'completed_by', 'status',
        'sign_in_completed_at', 'time_out_completed_at', 'sign_out_completed_at',
    ];

    protected $casts = [
        'sign_in_completed_at'  => 'datetime',
        'time_out_completed_at' => 'datetime',
        'sign_out_completed_at' => 'datetime',
    ];

    /**
     * WHO Surgical Safety Checklist default items by phase.
     */
    public const DEFAULT_ITEMS = [
        'sign_in' => [
            ['item_key' => 'patient_identity_confirmed', 'label' => 'Patient identity confirmed (name, DOB, wristband)'],
            ['item_key' => 'procedure_site_marked', 'label' => 'Procedure & site marked/confirmed'],
            ['item_key' => 'consent_form_signed', 'label' => 'Informed consent form signed'],
            ['item_key' => 'anaesthesia_check_complete', 'label' => 'Anaesthesia safety check complete'],
            ['item_key' => 'pulse_oximeter_functional', 'label' => 'Pulse oximeter on patient and functioning'],
            ['item_key' => 'known_allergy_confirmed', 'label' => 'Known allergies reviewed/confirmed'],
            ['item_key' => 'aspiration_risk_assessed', 'label' => 'Difficult airway/aspiration risk assessed'],
            ['item_key' => 'blood_loss_risk_assessed', 'label' => 'Risk of blood loss assessed (>500ml) — IV access/blood available'],
        ],
        'time_out' => [
            ['item_key' => 'team_introduction', 'label' => 'All team members introduced by name and role'],
            ['item_key' => 'patient_name_procedure_confirmed', 'label' => 'Patient name, procedure, and site confirmed'],
            ['item_key' => 'antibiotic_prophylaxis_given', 'label' => 'Antibiotic prophylaxis given within last 60 minutes'],
            ['item_key' => 'anticipated_critical_events_surgeon', 'label' => 'Surgeon: critical steps, duration, expected blood loss discussed'],
            ['item_key' => 'anticipated_critical_events_anaesthesia', 'label' => 'Anaesthesia: patient-specific concerns discussed'],
            ['item_key' => 'anticipated_critical_events_nursing', 'label' => 'Nursing: sterility confirmed, equipment issues addressed'],
            ['item_key' => 'essential_imaging_displayed', 'label' => 'Essential imaging displayed (if applicable)'],
        ],
        'sign_out' => [
            ['item_key' => 'procedure_name_recorded', 'label' => 'Name of procedure recorded'],
            ['item_key' => 'instrument_sponge_needle_count', 'label' => 'Instrument, sponge, and needle counts correct'],
            ['item_key' => 'specimen_labelled', 'label' => 'Specimen labelled correctly (if applicable)'],
            ['item_key' => 'equipment_problems_addressed', 'label' => 'Equipment problems addressed/documented'],
            ['item_key' => 'recovery_plan_communicated', 'label' => 'Key concerns for recovery communicated to team'],
        ],
    ];

    // ── Relationships ──

    public function surgery(): BelongsTo
    {
        return $this->belongsTo(Surgery::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SurgicalChecklistItem::class)->orderBy('phase')->orderBy('sort_order');
    }

    // ── Helpers ──

    public function isSignInComplete(): bool
    {
        return $this->items()->where('phase', 'sign_in')->where('is_checked', false)->doesntExist();
    }

    public function isTimeOutComplete(): bool
    {
        return $this->items()->where('phase', 'time_out')->where('is_checked', false)->doesntExist();
    }

    public function isSignOutComplete(): bool
    {
        return $this->items()->where('phase', 'sign_out')->where('is_checked', false)->doesntExist();
    }

    public function isFullyComplete(): bool
    {
        return $this->status === 'completed';
    }

    public function getPhaseProgress(string $phase): array
    {
        $total = $this->items()->where('phase', $phase)->count();
        $checked = $this->items()->where('phase', $phase)->where('is_checked', true)->count();
        return ['total' => $total, 'checked' => $checked];
    }
}
