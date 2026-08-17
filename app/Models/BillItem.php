<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class BillItem extends Model
{
    use Auditable, UsesTenantConnection;

    protected $fillable = [
        'bill_id',
        'service_id',
        'lab_test_id',
        'imaging_study_id',
        'item_category',
        'description',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTest::class);
    }

    public function imagingStudy(): BelongsTo
    {
        return $this->belongsTo(ImagingStudy::class);
    }

    public function catalogName(): ?string
    {
        return $this->labTest?->name ?? $this->imagingStudy?->name;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($billItem) {
            $billItem->total_price = $billItem->quantity * $billItem->unit_price;
        });
    }
}
