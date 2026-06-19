<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Surgery extends Model
{
    use Auditable, UsesTenantConnection;

    protected $fillable = [
        'surgery_number', 'patient_id', 'doctor_id', 'operation_theatre_id', 'visit_id',
        'surgery_type', 'procedure_name', 'procedure_code',
        'scheduled_date', 'scheduled_start_time', 'scheduled_end_time',
        'actual_start_time', 'actual_end_time',
        'pre_op_diagnosis', 'post_op_diagnosis',
        'procedure_notes', 'complications', 'anesthesia_type',
        'status', 'cancelled_reason', 'postponed_reason', 'created_by',
    ];

    protected $casts = [
        'scheduled_date'    => 'date',
        'actual_start_time' => 'datetime',
        'actual_end_time'   => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($surgery) {
            if (empty($surgery->surgery_number)) {
                $prefix = 'SURG-' . date('Ym') . '-';
                $lastNum = static::where('surgery_number', 'like', $prefix . '%')
                    ->selectRaw("MAX(CAST(SUBSTRING(surgery_number, ?) AS UNSIGNED)) as max_num", [strlen($prefix) + 1])
                    ->value('max_num');
                $surgery->surgery_number = $prefix . str_pad(($lastNum ?? 0) + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    // ── Relationships ──

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function operationTheatre(): BelongsTo
    {
        return $this->belongsTo(OperationTheatre::class, 'operation_theatre_id');
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(SurgeryTeamMember::class);
    }

    public function pacCheckup()
    {
        return $this->hasOne(PreAnaesthesiaCheckup::class);
    }

    public function surgicalChecklist()
    {
        return $this->hasOne(SurgicalChecklist::class);
    }

    public function consumableUsages(): HasMany
    {
        return $this->hasMany(OtConsumableUsage::class);
    }

    public function anaesthesiaRecord()
    {
        return $this->hasOne(AnaesthesiaRecord::class);
    }

    public function operativeVitals(): HasMany
    {
        return $this->hasMany(OperativeVital::class)->orderBy('recorded_at');
    }

    public function postOpMonitoring(): HasMany
    {
        return $this->hasMany(PostOpMonitoring::class)->orderBy('recorded_at');
    }

    // ── Scopes ──

    public function scopeScheduled(Builder $q): Builder
    {
        return $q->where('status', 'scheduled');
    }

    public function scopeToday(Builder $q): Builder
    {
        return $q->where('scheduled_date', today());
    }

    // ── Helpers ──

    public function getDurationMinutes(): ?int
    {
        if (!$this->actual_start_time || !$this->actual_end_time) return null;
        return $this->actual_start_time->diffInMinutes($this->actual_end_time);
    }
}
