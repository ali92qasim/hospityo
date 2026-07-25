<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class IpdConsultantVisit extends Model
{
    use Auditable, UsesTenantConnection;

    protected $fillable = [
        'visit_id',
        'consultant_doctor_id',
        'recorded_by',
        'visit_notes',
        'orders',
        'status',
        'consultant_seen_at',
    ];

    protected $casts = [
        'consultant_seen_at' => 'datetime',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function consultantDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'consultant_doctor_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default     => 'Pending',
        };
    }
}
