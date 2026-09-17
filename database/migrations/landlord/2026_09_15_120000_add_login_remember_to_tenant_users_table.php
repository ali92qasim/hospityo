<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('landlord')->table('tenant_users', function (Blueprint $table) {
            $table->boolean('login_remember')->default(false)->after('login_token');
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->table('tenant_users', function (Blueprint $table) {
            $table->dropColumn('login_remember');
        });
    }
};
