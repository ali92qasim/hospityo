<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Diagnosis extends Model
{
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
