<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

class SubscriptionPayment extends Model
{
    use UsesLandlordConnection;

    protected $fillable = [
        'subscription_id',
        'tenant_id',
        'payfast_transaction_id',
        'status',
        'amount',
        'currency',
        'payment_method',
        'payfast_response',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'           => 'decimal:2',
            'payfast_response' => 'array',
            'paid_at'          => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
