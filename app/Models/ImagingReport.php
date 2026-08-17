<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class ImagingReport extends Model
{
    use Auditable, UsesTenantConnection;

    protected $table = 'imaging_reports';

    protected $fillable = [
        'imaging_order_id',
        'report_text',
        'impression',
        'file_path',
        'status',
        'radiologist_id',
        'reported_at',
    ];

    protected $casts = [
        'reported_at' => 'datetime',
    ];

    public function imagingOrder(): BelongsTo
    {
        return $this->belongsTo(ImagingOrder::class);
    }

    public function investigationOrder(): BelongsTo
    {
        return $this->imagingOrder();
    }

    public function radiologist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'radiologist_id');
    }
}
