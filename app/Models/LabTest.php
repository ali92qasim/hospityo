<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class LabTest extends Model
{
    use Auditable, UsesTenantConnection;

    protected $table = 'lab_tests';

    protected $fillable = [
        'code', 'name', 'description', 'category', 'sample_type',
        'price', 'turnaround_time', 'instructions', 'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /** @return list<string> */
    public static function categories(): array
    {
        return [
            'hematology',
            'biochemistry',
            'microbiology',
            'immunology',
            'pathology',
            'histopathology',
            'molecular',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (LabTest $labTest): void {
            if (filled($labTest->category)) {
                $labTest->category = strtolower(trim((string) $labTest->category));
            }
        });
    }

    public function parameters(): HasMany
    {
        return $this->hasMany(LabTestParameter::class, 'lab_test_id')->orderBy('display_order');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(LabOrderItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
