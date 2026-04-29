<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Department extends Model
{
    use HasFactory, Auditable, UsesTenantConnection;

    protected $fillable = [
        'name', 'code', 'description', 'head_of_department', 'head_employee_id',
        'phone', 'email', 'location', 'monthly_budget', 'status',
    ];

    protected $casts = ['monthly_budget' => 'decimal:2'];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($department) {
            if (empty($department->code)) {
                $department->code = 'DEPT' . str_pad(
                    (Department::max('id') ?? 0) + 1, 3, '0', STR_PAD_LEFT
                );
            }
        });
    }

    public function doctors(): HasMany
    {
        return $this->hasMany(Doctor::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function headEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'head_employee_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    // ── HR Stats ──

    public function getActiveEmployeeCountAttribute(): int
    {
        return $this->employees()->where('status', 'active')->count();
    }

    public function getTotalSalaryCostAttribute(): float
    {
        return (float) $this->employees()->where('status', 'active')->sum('basic_salary');
    }
}
