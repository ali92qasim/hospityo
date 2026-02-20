<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Allergy extends Model
{
    use HasFactory;

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
