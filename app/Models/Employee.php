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
        'email', 'phone', 'cnic', 'gender',
        'date_of_birth', 'blood_group', 'address', 'city',
        'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation',
        'employment_type', 'joining_date', 'probation_end_date', 'contract_end_date',
        'termination_date', 'status',
        'basic_salary', 'expense_account_id', 'bank_name', 'bank_account_no', 'bank_branch',
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

        static::created(function (Employee $employee) {
            \App\Services\EmployeeAccountService::ensureExpenseAccount($employee);
        });

        static::updated(function (Employee $employee) {
            if ($employee->wasChanged(['name', 'employee_no', 'status'])) {
                \App\Services\EmployeeAccountService::ensureExpenseAccount($employee);
                \App\Services\EmployeeAccountService::syncAccountStatus($employee);
            }
        });

        static::deleting(function (Employee $employee) {
            if ($employee->expenseAccount) {
                $employee->expenseAccount->update(['is_active' => false]);
            }
        });
    }

    // ── Accessors ──
    public function getFullNameAttribute(): string
    {
        return $this->name ?? '';
    }

    public function getInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/', trim($this->name ?? ''), -1, PREG_SPLIT_NO_EMPTY);

        if (count($parts) >= 2) {
            return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
        }

        return strtoupper(substr($parts[0] ?? '?', 0, 2));
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

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
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
