<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Payslip extends Model
{
    use Auditable, UsesTenantConnection;

    protected $fillable = [
        'payroll_run_id', 'employee_id', 'payslip_no',
        'working_days', 'present_days', 'absent_days', 'leave_days', 'overtime_hours',
        'basic_salary', 'total_allowances', 'overtime_amount', 'gross_salary',
        'total_deductions', 'tax_amount', 'absent_deduction', 'loan_deduction',
        'net_salary', 'earnings_breakdown', 'deductions_breakdown',
        'payment_status', 'payment_method', 'payment_date', 'notes',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'total_allowances' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'absent_deduction' => 'decimal:2',
        'loan_deduction' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'earnings_breakdown' => 'array',
        'deductions_breakdown' => 'array',
        'payment_date' => 'date',
    ];

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($slip) {
            if (empty($slip->payslip_no)) {
                $slip->payslip_no = 'PS-' . date('Ym') . '-' . str_pad(
                    (static::whereYear('created_at', date('Y'))->count() + 1), 5, '0', STR_PAD_LEFT
                );
            }
        });
    }
}
