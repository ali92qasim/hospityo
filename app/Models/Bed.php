<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function admissions()
    {
        return $this->hasMany(Admission::class);
    }

    public function currentAdmission()
    {
        return $this->hasOne(Admission::class)->where('status', 'active');
    }
}