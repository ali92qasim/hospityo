<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class InvestigationOrderItem extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'investigation_order_id',
        'investigation_id',
        'quantity',
        'priority',
        'status',
        'clinical_notes',
        'test_location',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(InvestigationOrder::class, 'investigation_order_id');
    }

    public function investigation(): BelongsTo
    {
        return $this->belongsTo(Investigation::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(LabResult::class, 'investigation_order_id', 'investigation_order_id');
    }

    public function sample(): HasOne
    {
        return $this->hasOne(LabSample::class, 'investigation_order_id', 'investigation_order_id');
    }

    public function isPathology(): bool
    {
        return in_array($this->investigation?->category, [
            'hematology',
            'biochemistry',
            'microbiology',
            'immunology',
            'pathology',
            'molecular',
        ]);
    }

    public function hasParameters(): bool
    {
        return $this->investigation?->parameters?->count() > 0;
    }
}
