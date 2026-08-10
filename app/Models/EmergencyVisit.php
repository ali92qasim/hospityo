<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class EmergencyVisit extends Model
{
    use UsesTenantConnection;

    protected $primaryKey = 'visit_id';

    public $incrementing = false;

    protected $fillable = [
        'visit_id',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }
}
