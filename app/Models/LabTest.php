<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabTest extends Model
{
    protected $fillable = [
        'code', 'name', 'description', 'category', 'sample_type', 
        'price', 'turnaround_time', 'parameters', 'instructions', 'is_active'
    ];

    protected $casts = [
        'parameters' => 'array',
        'price' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function orders()
    {
        return $this->hasMany(LabOrder::class);
    }

    public function parameters()
    {
        return $this->hasMany(LabTestParameter::class)->orderBy('display_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}