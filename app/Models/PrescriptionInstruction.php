<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class PrescriptionInstruction extends Model
{
    use HasFactory, Auditable, UsesTenantConnection;

    protected $fillable = [
        'title',
        'instruction',
        'category',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function prescriptions(): BelongsToMany
    {
        return $this->belongsToMany(Prescription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
