<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consultation extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id',
        'presenting_complaints',
        'history',
        'examination',
        'provisional_diagnosis',
        'treatment',
        'notes'
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }
}
