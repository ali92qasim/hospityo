<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class PayrollRun extends Model
{
    use Auditable, UsesTenantConnection;

    protected $fillable = [
        'title', 'year', 'month', 'status', 'total_employees',
        'total_gross', 'total_deductions', 'total_net',
        'created_by', 'approved_by', 'approved_at', 'notes',
    ];

    protected $casts = [
        'total_gross' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_net' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function recalculateTotals(): void
    {
        $this->total_employees = $this->payslips()->count();
        $this->total_gross = $this->payslips()->sum('gross_salary');
        $this->total_deductions = $this->payslips()->sum('total_deductions');
        $this->total_net = $this->payslips()->sum('net_salary');
        $this->save();
    }
}
