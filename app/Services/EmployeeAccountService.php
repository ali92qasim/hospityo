<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeAccountService
{
    private const PARENT_CODE = '5300';

    /**
     * Build a unique expense account code for an employee (53001–53999+).
     */
    public static function expenseAccountCode(int $employeeId): string
    {
        return '53' . str_pad((string) $employeeId, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Ensure the employee has a linked salary expense account in the chart of accounts.
     */
    public static function ensureExpenseAccount(Employee $employee): ?Account
    {
        try {
            return DB::connection('tenant')->transaction(function () use ($employee) {
                $parent = Account::firstOrCreate(
                    ['code' => self::PARENT_CODE],
                    [
                        'name' => 'Salaries & Wages',
                        'type' => 'expense',
                        'is_system' => false,
                        'is_active' => true,
                    ]
                );

                $code = self::expenseAccountCode($employee->id);
                $name = self::accountName($employee);

                $account = Account::firstOrCreate(
                    ['code' => $code],
                    [
                        'name' => $name,
                        'type' => 'expense',
                        'parent_id' => $parent->id,
                        'description' => "Salary expense for {$employee->employee_no}",
                        'is_system' => false,
                        'is_active' => self::isActiveStatus($employee->status),
                    ]
                );

                $updates = [];

                if ($account->parent_id !== $parent->id) {
                    $updates['parent_id'] = $parent->id;
                }

                if ($account->name !== $name) {
                    $updates['name'] = $name;
                }

                $active = self::isActiveStatus($employee->status);
                if ($account->is_active !== $active) {
                    $updates['is_active'] = $active;
                }

                if ($updates !== []) {
                    $account->update($updates);
                }

                if ($employee->expense_account_id !== $account->id) {
                    $employee->updateQuietly(['expense_account_id' => $account->id]);
                }

                return $account->fresh();
            });
        } catch (\Throwable $e) {
            Log::error('[HR] Failed to provision employee expense account', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Resolve the expense account to use for payroll posting.
     */
    public static function resolveExpenseAccount(Employee $employee): ?Account
    {
        if ($employee->relationLoaded('expenseAccount') && $employee->expenseAccount) {
            return $employee->expenseAccount;
        }

        if ($employee->expense_account_id) {
            return Account::find($employee->expense_account_id);
        }

        return self::ensureExpenseAccount($employee);
    }

    public static function syncAccountStatus(Employee $employee): void
    {
        if (! $employee->expense_account_id) {
            return;
        }

        $account = Account::find($employee->expense_account_id);
        if (! $account) {
            return;
        }

        $account->update([
            'is_active' => self::isActiveStatus($employee->status),
            'name' => self::accountName($employee),
        ]);
    }

    private static function accountName(Employee $employee): string
    {
        return 'Salary — ' . $employee->full_name . ' (' . $employee->employee_no . ')';
    }

    private static function isActiveStatus(?string $status): bool
    {
        return in_array($status, ['active', 'on_leave'], true);
    }
}
