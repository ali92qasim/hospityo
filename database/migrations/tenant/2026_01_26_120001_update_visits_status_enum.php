<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        Schema::table('visits', function (Blueprint $table) {
            $table->enum('status', [
                'registered', 
                'triaged', 
                'vitals_recorded', 
                'admitted', 
                'with_doctor', 
                'tests_ordered', 
                'tests_completed', 
                'discharged', 
                'completed'
            ])->default('registered');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        Schema::table('visits', function (Blueprint $table) {
            $table->enum('status', ['registered', 'vitals_recorded', 'with_doctor', 'tests_ordered', 'tests_completed', 'completed'])->default('registered');
        });
    }
};