<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class DocumentRequirement extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'document_type', 'label', 'applicable_to', 'is_mandatory',
        'has_expiry', 'expiry_reminder_days', 'description', 'is_active',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'has_expiry' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /**
     * Get requirements applicable to an employee based on their designation category.
     */
    public static function getForEmployee(Employee $employee): \Illuminate\Support\Collection
    {
        $category = $employee->designation?->category ?? 'admin';

        return static::active()->get()->filter(function ($req) use ($category) {
            if ($req->applicable_to === 'all') return true;
            if ($req->applicable_to === $category) return true;
            if (str_starts_with($req->applicable_to, 'designation:')) {
                return $employee->designation_id == (int) str_replace('designation:', '', $req->applicable_to);
            }
            return false;
        });
    }
}
