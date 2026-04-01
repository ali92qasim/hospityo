<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->datetime('appointment_datetime')->nullable()->after('doctor_id');
            $table->dropColumn(['appointment_date', 'appointment_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->date('appointment_date')->after('department_id');
            $table->time('appointment_time')->after('appointment_date');
            $table->dropColumn('appointment_datetime');
        });
    }
};
