<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id',
        'test_name',
        'quantity',
        'priority',
        'clinical_notes',
        'instructions',
        'status',
        'results',
        'ordered_at',
        'completed_at'
    ];

    protected $casts = [
        'ordered_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    protected $attributes = [
        'status' => 'ordered'
    ];

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }
}