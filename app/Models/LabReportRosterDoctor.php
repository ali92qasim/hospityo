<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class LabReportRosterDoctor extends Model
{
    use UsesTenantConnection;

    public $timestamps = false;

    protected $fillable = [
        'doctor_id',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
