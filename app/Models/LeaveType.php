<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class LeaveType extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'name', 'code', 'default_days', 'is_paid', 'is_carry_forward',
        'max_carry_forward_days', 'requires_document', 'is_active', 'description',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'is_carry_forward' => 'boolean',
        'requires_document' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }
}
