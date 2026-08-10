<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class VisitClassHistory extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'visit_id',
        'from_type',
        'to_type',
        'changed_at',
        'changed_by',
        'reason',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
