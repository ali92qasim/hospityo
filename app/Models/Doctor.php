<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    protected static function boot(): void
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function assignedPatients(): HasMany
    {
        return $this->visits()->whereIn('status', ['registered', 'vitals_recorded', 'with_doctor', 'triaged'])
                    ->with('patient')
                    ->latest();
    }
}
