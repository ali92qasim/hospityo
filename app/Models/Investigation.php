<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Investigation extends Model
{
    protected $fillable = [
        'code', 'name', 'type', 'description', 'category', 'sample_type',
        'price', 'turnaround_time', 'instructions', 'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(InvestigationOrder::class);
    }

    public function parameters(): HasMany
    {
        return $this->hasMany(LabTestParameter::class, 'lab_test_id')->orderBy('display_order', 'asc');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', '=', $category);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', '=', $type);
    }
}
