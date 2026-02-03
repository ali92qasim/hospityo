<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Triage extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id',
        'priority_level',
        'chief_complaint',
        'pain_scale',
        'triage_notes',
        'triaged_by',
        'triaged_at'
    ];

    protected $casts = [
        'triaged_at' => 'datetime'
    ];

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function triager()
    {
        return $this->belongsTo(User::class, 'triaged_by');
    }
}