<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::connection($this->getConnection())->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite enum CHECK blocks new values — recreate status as free string.
            Schema::table('bills', function (Blueprint $table) {
                $table->dropIndex(['status']);
            });

            Schema::table('bills', function (Blueprint $table) {
                $table->dropColumn('status');
            });

            Schema::table('bills', function (Blueprint $table) {
                $table->string('status', 20)->default('pending')->index();
            });

            return;
        }

        DB::statement("ALTER TABLE `bills` MODIFY `status` ENUM('draft', 'pending', 'partial', 'paid', 'cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        $driver = Schema::connection($this->getConnection())->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        DB::table('bills')->where('status', 'draft')->update(['status' => 'pending']);

        DB::statement("ALTER TABLE `bills` MODIFY `status` ENUM('pending', 'partial', 'paid', 'cancelled') NOT NULL DEFAULT 'pending'");
    }
};
