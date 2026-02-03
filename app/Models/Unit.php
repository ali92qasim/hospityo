<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function baseUnit()
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function derivedUnits()
    {
        return $this->hasMany(Unit::class, 'base_unit_id');
    }

    public function medicines()
    {
        return $this->hasMany(Medicine::class, 'base_unit_id');
    }

    public function convertToBaseUnit($quantity)
    {
        return $quantity * $this->conversion_factor;
    }

    public function convertFromBaseUnit($baseQuantity)
    {
        return $baseQuantity / $this->conversion_factor;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBaseUnits($query)
    {
        return $query->whereNull('base_unit_id');
    }
}