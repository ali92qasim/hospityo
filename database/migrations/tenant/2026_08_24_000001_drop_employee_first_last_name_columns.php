<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('employees', 'name')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('name')->nullable()->after('employee_no');
            });
        }

        if (Schema::hasColumn('employees', 'first_name') && Schema::hasColumn('employees', 'last_name')) {
            DB::table('employees')
                ->where(function ($query) {
                    $query->whereNull('name')->orWhere('name', '');
                })
                ->update([
                    'name' => DB::raw("TRIM(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')))"),
                ]);

            DB::table('employees')
                ->where(function ($query) {
                    $query->whereNull('name')->orWhere('name', '');
                })
                ->update([
                    'name' => DB::raw('employee_no'),
                ]);

            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn(['first_name', 'last_name']);
            });
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('designation_id');
            $table->string('last_name')->nullable()->after('first_name');
        });

        $employees = DB::table('employees')->orderBy('id')->get(['id', 'name']);

        foreach ($employees as $employee) {
            $name = trim($employee->name ?? '');
            $space = strpos($name, ' ');

            DB::table('employees')->where('id', $employee->id)->update([
                'first_name' => $space === false ? $name : substr($name, 0, $space),
                'last_name' => $space === false ? '' : trim(substr($name, $space + 1)),
            ]);
        }
    }
};
