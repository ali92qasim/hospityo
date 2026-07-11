<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('services', 'doctor_id')) {
            return;
        }

        Schema::table('services', function (Blueprint $table) {
            $table->dropConstrainedForeignId('doctor_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('services', 'doctor_id')) {
            return;
        }

        Schema::table('services', function (Blueprint $table) {
            $table->foreignId('doctor_id')
                ->nullable()
                ->after('department_id')
                ->constrained()
                ->nullOnDelete();
        });
    }
};
