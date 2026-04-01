<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Department extends Model
{
    use HasFactory, Auditable, UsesTenantConnection;

    protected $fillable = [
        'name',
        'code',
        'description',
        'head_of_department',
        'phone',
        'email',
        'location',
        'status',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($department) {
            $department->code = 'DEPT' . str_pad(
                (Department::max('id') ?? 0) + 1,
                3,
                '0',
                STR_PAD_LEFT
            );
        });
    }

    public function doctors(): HasMany
    {
        return $this->hasMany(Doctor::class);
    }
}
