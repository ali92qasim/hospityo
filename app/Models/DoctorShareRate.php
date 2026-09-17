<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class DoctorShareRate extends Model
{
    use UsesTenantConnection;

    public const CATEGORIES = [
        'general',
        'opd',
        'ipd',
        'emergency',
        'lab',
        'imaging',
        'pharmacy',
    ];

    protected $fillable = [
        'doctor_id',
        'service_category',
        'percentage',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
