<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class IpdVisit extends Model
{
    use UsesTenantConnection;

    protected $primaryKey = 'visit_id';

    public $incrementing = false;

    protected $fillable = [
        'visit_id',
        'expected_discharge_date',
    ];

    protected $casts = [
        'expected_discharge_date' => 'date',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }
}
