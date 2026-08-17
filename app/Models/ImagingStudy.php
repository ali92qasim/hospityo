<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class ImagingStudy extends Model
{
    use Auditable, UsesTenantConnection;

    protected $table = 'imaging_studies';

    protected $fillable = [
        'code', 'name', 'description', 'category',
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
            'x-ray',
            'ultrasound',
            'ct-scan',
            'mri',
            'radiology',
            'cardiology',
            'cardiac-diagnostics',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ImagingStudy $study): void {
            if (filled($study->category)) {
                $study->category = strtolower(trim((string) $study->category));
            }
        });
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(ImagingOrderItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
