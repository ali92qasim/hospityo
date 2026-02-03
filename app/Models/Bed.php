<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
class Bed extends Model
{
    use HasFactory;

    protected $fillable = [
        'bed_number',
        'ward_id',
        'bed_type',
        'status',
        'daily_rate'
    ];

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function admissions(): HasMany
    {
        return $this->hasMany(Admission::class);
    }

    public function currentAdmission(): HasOne
    {
        return $this->hasOne(Admission::class)->where('status', 'active');
    }
}
