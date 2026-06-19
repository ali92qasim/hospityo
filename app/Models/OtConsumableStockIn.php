<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class OtConsumableStockIn extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'ot_consumable_id', 'quantity', 'remaining_quantity',
        'unit_cost', 'batch_no', 'expiry_date', 'serial_number',
        'supplier_name', 'reference_no', 'created_by',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'unit_cost'   => 'decimal:2',
    ];

    public function consumable(): BelongsTo
    {
        return $this->belongsTo(OtConsumable::class, 'ot_consumable_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
