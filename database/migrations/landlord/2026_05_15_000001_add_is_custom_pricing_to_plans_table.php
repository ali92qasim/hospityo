<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('landlord')->table('plans', function (Blueprint $table) {
            $table->boolean('is_custom_pricing')->default(false)->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->table('plans', function (Blueprint $table) {
            $table->dropColumn('is_custom_pricing');
        });
    }
};
