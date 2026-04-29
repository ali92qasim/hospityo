<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class ShiftSwapRequest extends Model
{
    use Auditable, UsesTenantConnection;

    protected $fillable = [
        'requester_id', 'target_id', 'swap_date',
        'requester_shift_id', 'target_shift_id',
        'reason', 'status', 'approved_by', 'approved_at',
    ];

    protected $casts = ['swap_date' => 'date', 'approved_at' => 'datetime'];

    public function requester(): BelongsTo { return $this->belongsTo(Employee::class, 'requester_id'); }
    public function target(): BelongsTo { return $this->belongsTo(Employee::class, 'target_id'); }
    public function requesterShift(): BelongsTo { return $this->belongsTo(Shift::class, 'requester_shift_id'); }
    public function targetShift(): BelongsTo { return $this->belongsTo(Shift::class, 'target_shift_id'); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
}
