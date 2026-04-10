<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

class Plan extends Model
{
    use UsesLandlordConnection;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'price',
        'billing_cycle',
        'trial_days',
        'modules',
        'limits',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'modules'   => 'array',
            'limits'    => 'array',
            'price'     => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Check if this plan includes a specific module.
     */
    public function hasModule(string $module): bool
    {
        return in_array($module, $this->modules ?? [], true);
    }

    /**
     * Get a specific limit value, or default if not set.
     */
    public function getLimit(string $key, mixed $default = null): mixed
    {
        return $this->limits[$key] ?? $default;
    }

    /**
     * Tenants on this plan.
     */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    /**
     * Scope to active plans only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
