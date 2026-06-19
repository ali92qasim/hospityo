<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Employee extends Model
{
    use Auditable, UsesTenantConnection;

    protected $fillable = [
        'employee_no', 'name', 'user_id', 'doctor_id', 'department_id', 'designation_id',
        'first_name', 'last_name', 'email', 'phone', 'cnic', 'gender',
        'date_of_birth', 'blood_group', 'address', 'city',
        'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation',
        'employment_type', 'joining_date', 'probation_end_date', 'contract_end_date',
        'termination_date', 'status',
        'basic_salary', 'bank_name', 'bank_account_no', 'bank_branch',
        'default_shift', 'shift_start', 'shift_end',
        'photo', 'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'joining_date' => 'date',
        'probation_end_date' => 'date',
        'contract_end_date' => 'date',
        'termination_date' => 'date',
        'basic_salary' => 'decimal:2',
    ];

    protected $appends = ['full_name'];

    // ── Auto-generate employee number ──
    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($emp) {
            if (empty($emp->employee_no)) {
                $emp->employee_no = 'EMP' . str_pad((static::max('id') ?? 0) + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    // ── Accessors ──
    public function getFullNameAttribute(): string
    {
        return $this->name ?? trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    // ── Relationships ──
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    // ── Scopes ──
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 'active');
    }

    public function scopeByDepartment(Builder $q, int $deptId): Builder
    {
        return $q->where('department_id', $deptId);
    }

    public function scopeByType(Builder $q, string $type): Builder
    {
        return $q->where('employment_type', $type);
    }
}
