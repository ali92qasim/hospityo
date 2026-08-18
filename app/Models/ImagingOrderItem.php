<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class ImagingOrderItem extends Model
{
    use UsesTenantConnection;

    protected $table = 'imaging_order_items';

    protected $fillable = [
        'imaging_order_id',
        'imaging_study_id',
        'quantity',
        'priority',
        'status',
        'clinical_notes',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ImagingOrder::class, 'imaging_order_id');
    }

    public function imagingStudy(): BelongsTo
    {
        return $this->belongsTo(ImagingStudy::class);
    }

    public function investigation(): BelongsTo
    {
        return $this->imagingStudy();
    }
}
