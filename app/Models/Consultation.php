<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'diagnosis_dm',
        'diagnosis_htn',
        'diagnosis_ihd',
        'diagnosis_asthma',
        'allergy_notes',
        'gpe_chest',
        'gpe_abdomen',
        'gpe_cvs',
        'gpe_cns',
        'gpe_pupils',
        'gpe_conjunctiva',
        'gpe_nails',
        'gpe_throat',
        'gpe_sclera',
        'gpe_gcs',
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

    public function allergies(): BelongsToMany
    {
        return $this->belongsToMany(Allergy::class, 'consultation_allergy');
    }
}
