<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('head_employee_id')->nullable()->after('head_of_department')
                  ->constrained('employees')->nullOnDelete();
            $table->decimal('monthly_budget', 15, 2)->nullable()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('head_employee_id');
            $table->dropColumn('monthly_budget');
        });
    }
};
