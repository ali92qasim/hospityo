<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

class Subscription extends Model
{
    use UsesLandlordConnection;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'payfast_subscription_id',
        'payfast_transaction_id',
        'status',
        'gateway',
        'gateway_subscription_id',
        'gateway_customer_id',
        'amount',
        'currency',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'cancelled_at',
        'payfast_meta',
    ];

    protected function casts(): array
    {
        return [
            'amount'         => 'decimal:2',
            'payfast_meta'   => 'array',
            'starts_at'      => 'datetime',
            'ends_at'        => 'datetime',
            'trial_ends_at'  => 'datetime',
            'cancelled_at'   => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && (! $this->ends_at || $this->ends_at->isFuture());
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
