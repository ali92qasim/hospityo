<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Allergy extends Model
{
    use HasFactory, Auditable, UsesTenantConnection;

    protected $fillable = [
        'name',
        'category',
        'is_standard'
    ];

    protected $casts = [
        'is_standard' => 'boolean'
    ];

    public function consultations(): BelongsToMany
    {
        return $this->belongsToMany(Consultation::class, 'consultation_allergy');
    }
}
