<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class LabOrderItem extends Model
{
    use UsesTenantConnection;

    protected $table = 'lab_order_items';

    protected $fillable = [
        'lab_order_id',
        'lab_test_id',
        'quantity',
        'priority',
        'status',
        'clinical_notes',
        'test_location',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(LabOrder::class, 'lab_order_id');
    }

    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTest::class);
    }

    public function investigation(): BelongsTo
    {
        return $this->labTest();
    }

    public function result(): HasOne
    {
        return $this->hasOne(LabResult::class, 'lab_order_id', 'lab_order_id');
    }

    public function sample(): HasOne
    {
        return $this->hasOne(LabSample::class, 'lab_order_id', 'lab_order_id');
    }

    public function hasResult(): bool
    {
        return $this->relationLoaded('result')
            ? $this->result !== null
            : $this->result()->exists();
    }
}
