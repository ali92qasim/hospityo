<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('emergency_name')->nullable()->change();
            $table->string('emergency_phone')->nullable()->change();
            $table->string('emergency_relation')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('emergency_name')->nullable(false)->change();
            $table->string('emergency_phone')->nullable(false)->change();
            $table->string('emergency_relation')->nullable(false)->change();
        });
    }
};
