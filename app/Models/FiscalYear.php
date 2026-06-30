<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class FiscalYear extends Model
{
    use UsesTenantConnection;

    protected $fillable = ['name', 'start_date', 'end_date', 'is_active', 'is_closed', 'closed_at', 'closed_by'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'is_closed' => 'boolean',
        'closed_at' => 'datetime',
    ];

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->where('is_closed', false);
    }

    public static function current(): ?self
    {
        return static::active()->first();
    }
}
