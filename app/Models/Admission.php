<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admission extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id',
        'bed_id',
        'admission_date',
        'discharge_date',
        'status',
        'admission_notes',
        'discharge_notes',
        'discharge_summary'
    ];

    protected $casts = [
        'admission_date' => 'datetime',
        'discharge_date' => 'datetime'
    ];

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function bed()
    {
        return $this->belongsTo(Bed::class);
    }
}