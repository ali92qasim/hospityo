<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ward extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'department_id',
        'capacity',
        'ward_type',
        'status'
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function beds()
    {
        return $this->hasMany(Bed::class);
    }

    public function availableBeds()
    {
        return $this->beds()->where('status', 'available');
    }
}