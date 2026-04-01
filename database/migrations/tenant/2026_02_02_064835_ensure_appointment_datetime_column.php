<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Check if appointment_datetime column doesn't exist
            if (!Schema::hasColumn('appointments', 'appointment_datetime')) {
                $table->datetime('appointment_datetime')->after('doctor_id');
            }
            
            // Drop old columns if they exist
            if (Schema::hasColumn('appointments', 'appointment_date')) {
                $table->dropColumn('appointment_date');
            }
            if (Schema::hasColumn('appointments', 'appointment_time')) {
                $table->dropColumn('appointment_time');
            }
            if (Schema::hasColumn('appointments', 'department_id')) {
                $table->dropColumn('department_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'appointment_date')) {
                $table->date('appointment_date')->after('doctor_id');
            }
            if (!Schema::hasColumn('appointments', 'appointment_time')) {
                $table->time('appointment_time')->after('appointment_date');
            }
            if (Schema::hasColumn('appointments', 'appointment_datetime')) {
                $table->dropColumn('appointment_datetime');
            }
        });
    }
};