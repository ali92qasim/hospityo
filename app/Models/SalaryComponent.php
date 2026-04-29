<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class SalaryComponent extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'name', 'code', 'type', 'calculation', 'default_amount',
        'percentage_of', 'is_taxable', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'default_amount' => 'decimal:2',
        'is_taxable' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $q): Builder { return $q->where('is_active', true); }
    public function scopeAllowances(Builder $q): Builder { return $q->where('type', 'allowance'); }
    public function scopeDeductions(Builder $q): Builder { return $q->where('type', 'deduction'); }

    /**
     * Calculate amount based on type (fixed or percentage of base).
     */
    public function calculate(float $basicSalary, float $grossSalary, ?float $override = null): float
    {
        $amount = $override ?? $this->default_amount;

        if ($this->calculation === 'percentage') {
            $base = $this->percentage_of === 'gross_salary' ? $grossSalary : $basicSalary;
            return round($base * $amount / 100, 2);
        }

        return round($amount, 2);
    }
}
