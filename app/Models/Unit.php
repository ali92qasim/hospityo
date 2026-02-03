<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'abbreviation',
        'base_unit_id',
        'conversion_factor',
        'type',
        'is_active'
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:4',
        'is_active' => 'boolean'
    ];

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function derivedUnits(): HasMany
    {
        return $this->hasMany(Unit::class, 'base_unit_id');
    }

    public function medicines(): HasMany
    {
        return $this->hasMany(Medicine::class, 'base_unit_id');
    }

    public function convertToBaseUnit(float $quantity): float
    {
        return $quantity * $this->conversion_factor;
    }

    public function convertFromBaseUnit(float $baseQuantity): float
    {
        return $baseQuantity / $this->conversion_factor;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeBaseUnits(Builder $query): Builder
    {
        return $query->whereNull('base_unit_id');
    }
}