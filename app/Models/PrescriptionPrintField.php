<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class PrescriptionPrintField extends Model
{
    use Auditable, UsesTenantConnection;

    protected $fillable = [
        'template_id',
        'field_key',
        'x_mm',
        'y_mm',
        'font_size',
        'font_weight',
        'align',
        'visible',
    ];

    protected $casts = [
        'x_mm' => 'float',
        'y_mm' => 'float',
        'font_size' => 'float',
        'visible' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(PrescriptionPrintTemplate::class, 'template_id');
    }
}
