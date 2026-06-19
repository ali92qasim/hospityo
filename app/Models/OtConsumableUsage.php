<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class OtConsumableUsage extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'surgery_id', 'ot_consumable_id', 'stock_in_id',
        'quantity_used', 'serial_number', 'notes', 'recorded_by',
    ];

    public function surgery(): BelongsTo
    {
        return $this->belongsTo(Surgery::class);
    }

    public function consumable(): BelongsTo
    {
        return $this->belongsTo(OtConsumable::class, 'ot_consumable_id');
    }

    public function stockIn(): BelongsTo
    {
        return $this->belongsTo(OtConsumableStockIn::class, 'stock_in_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
