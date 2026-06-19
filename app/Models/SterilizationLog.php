<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class SterilizationLog extends Model
{
    use Auditable, UsesTenantConnection;

    protected $fillable = [
        'log_number', 'target_type', 'operation_theatre_id', 'ot_consumable_id',
        'instrument_set_name', 'method', 'cycle_number',
        'temperature', 'duration_minutes',
        'chemical_indicator_result', 'biological_indicator_result',
        'status', 'scheduled_at', 'started_at', 'completed_at',
        'performed_by', 'verified_by', 'verified_at',
        'notes', 'failure_reason', 'created_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
        'verified_at'  => 'datetime',
    ];

    public const METHODS = [
        'autoclave'       => 'Steam Autoclave',
        'chemical'        => 'Chemical (Glutaraldehyde)',
        'dry_heat'        => 'Dry Heat',
        'ethylene_oxide'  => 'Ethylene Oxide (EtO)',
        'plasma'          => 'Hydrogen Peroxide Plasma',
    ];

    public const TARGET_TYPES = [
        'theatre'              => 'Operation Theatre',
        'instrument_set'       => 'Instrument Set',
        'individual_instrument'=> 'Individual Instrument',
    ];

    // ── Relationships ──

    public function theatre(): BelongsTo
    {
        return $this->belongsTo(OperationTheatre::class, 'operation_theatre_id');
    }

    public function consumable(): BelongsTo
    {
        return $this->belongsTo(OtConsumable::class, 'ot_consumable_id');
    }

    public function performedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Helpers ──

    public function isVerified(): bool
    {
        return $this->verified_by !== null;
    }

    public function hasPassed(): bool
    {
        return $this->status === 'completed'
            && $this->chemical_indicator_result !== 'fail'
            && $this->biological_indicator_result !== 'fail';
    }

    // ── Boot — auto-generate log number ──

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($log) {
            if (empty($log->log_number)) {
                $prefix = 'STER-' . date('Ymd') . '-';
                $last = static::where('log_number', 'like', $prefix . '%')
                    ->selectRaw("MAX(CAST(SUBSTRING(log_number, ?) AS UNSIGNED)) as max_num", [strlen($prefix) + 1])
                    ->value('max_num');
                $log->log_number = $prefix . str_pad(($last ?? 0) + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
