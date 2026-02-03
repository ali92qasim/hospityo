<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'doctor_no',
        'specialization',
        'qualification',
        'phone',
        'email',
        'gender',
        'experience_years',
        'address',
        'consultation_fee',
        'available_days',
        'shift_start',
        'shift_end',
        'status',
        'department_id',
    ];

    protected $casts = [
        'available_days' => 'array',
        'consultation_fee' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($doctor) {
            $doctor->doctor_no = 'DR' . str_pad(
                (Doctor::max('id') ?? 0) + 1,
                4,
                '0',
                STR_PAD_LEFT
            );
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}