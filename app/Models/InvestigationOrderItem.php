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

    public function hasResult(): bool
    {
        return $this->status === 'reported';
    }

    /**
     * The lab result for this item's parent order.
     * LabResult is stored per-order (investigation_order_id), not per-item,
     * so this relationship proxies through the parent order's FK.
     */
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

    public function isRadiology(): bool
    {
        return in_array($this->investigation?->category, [
            'x-ray',
            'ultrasound',
            'ct-scan',
            'mri',
            'radiology',
            'cardiology',
        ]);
    }

    public function hasParameters(): bool
    {
        return $this->investigation?->parameters?->count() > 0;
    }
}
