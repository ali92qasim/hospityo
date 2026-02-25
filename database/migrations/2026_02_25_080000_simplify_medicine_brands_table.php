<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicine_brands', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });
        
        Schema::table('medicine_brands', function (Blueprint $table) {
            $table->dropColumn(['code', 'manufacturer', 'country']);
        });
    }

    public function down(): void
    {
        Schema::table('medicine_brands', function (Blueprint $table) {
            $table->string('code')->unique()->after('name');
            $table->string('manufacturer')->nullable()->after('code');
            $table->string('country')->nullable()->after('manufacturer');
        });
    }
};
