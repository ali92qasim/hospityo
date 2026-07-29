<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class IpdDoctorVisitNote extends Model
{
    use Auditable, UsesTenantConnection;

    protected $fillable = [
        'visit_id',
        'doctor_id',
        'notes',
        'orders',
        'status',
        'visited_at',
        'created_by',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
