<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class SurgicalChecklistItem extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'surgical_checklist_id', 'phase', 'item_key', 'label',
        'is_checked', 'checked_by', 'checked_at', 'notes', 'sort_order',
    ];

    protected $casts = [
        'is_checked' => 'boolean',
        'checked_at' => 'datetime',
    ];

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(SurgicalChecklist::class, 'surgical_checklist_id');
    }

    public function checkedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
