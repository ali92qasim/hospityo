<?php

use App\Models\Employee;
use App\Services\EmployeeAccountService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('expense_account_id')
                ->nullable()
                ->after('basic_salary')
                ->constrained('accounts')
                ->nullOnDelete();
        });

        Employee::query()->each(function (Employee $employee) {
            EmployeeAccountService::ensureExpenseAccount($employee);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('expense_account_id');
        });
    }
};
