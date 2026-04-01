<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Diagnosis extends Model
{
    use Auditable, UsesTenantConnection;

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
