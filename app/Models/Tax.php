<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Tax extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'name', 'code', 'percentage', 'is_inclusive', 'is_active', 'description',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'is_inclusive' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function mappings(): HasMany
    {
        return $this->hasMany(TaxMapping::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get all taxes applicable to a given bill type.
     * A tax applies if it has:
     *   - A mapping with applicable_on='all' (global tax), OR
     *   - A mapping with applicable_on='bill_type' and applicable_value matching the bill type, OR
     *   - A mapping with applicable_on='service_category' and applicable_value='all' (all service categories)
     */
    public static function getApplicableTaxes(?string $billType = null): \Illuminate\Support\Collection
    {
        return static::active()
            ->whereHas('mappings', function ($q) use ($billType) {
                $q->where(function ($sub) use ($billType) {
                    // Global: applies to everything
                    $sub->where('applicable_on', 'all');

                    // Bill type specific
                    if ($billType) {
                        $sub->orWhere(function ($w) use ($billType) {
                            $w->where('applicable_on', 'bill_type')
                              ->where('applicable_value', $billType);
                        });
                    }

                    // Service category = all (applies to all service categories)
                    $sub->orWhere(function ($w) {
                        $w->where('applicable_on', 'service_category')
                          ->where('applicable_value', 'all');
                    });
                });
            })
            ->get()
            ->unique('id'); // Prevent duplicate taxes
    }

    /**
     * Calculate tax amount for a given subtotal.
     */
    public function calculateTax(float $amount): float
    {
        if ($this->is_inclusive) {
            return round($amount - ($amount / (1 + $this->percentage / 100)), 2);
        }
        return round($amount * $this->percentage / 100, 2);
    }
}
