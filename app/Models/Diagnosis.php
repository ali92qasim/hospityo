<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class Diagnosis extends Model
{
    use Auditable;

    protected $fillable = [
        'icd_10_code',
        'diagnosis_name',
        'description',
        'type',
        'status',
        'onset_date',
        'resolved_date',
        'notes',
    ];

    protected $casts = [
        'onset_date' => 'date',
        'resolved_date' => 'date',
    ];


}
