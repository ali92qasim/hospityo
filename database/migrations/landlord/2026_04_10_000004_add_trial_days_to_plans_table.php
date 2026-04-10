<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('landlord')->table('plans', function (Blueprint $table) {
            $table->unsignedInteger('trial_days')->default(14)->after('billing_cycle');
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->table('plans', function (Blueprint $table) {
            $table->dropColumn('trial_days');
        });
    }
};
