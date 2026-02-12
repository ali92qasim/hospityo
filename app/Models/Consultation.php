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
        'chief_complaint',
        'presenting_complaints',
        'history',
        'examination',
        'provisional_diagnosis',
        'treatment',
        'treatment_plan',
        'follow_up_instructions',
        'notes',
        'next_visit_date'
    ];

    protected $casts = [
        'next_visit_date' => 'date'
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }
}
