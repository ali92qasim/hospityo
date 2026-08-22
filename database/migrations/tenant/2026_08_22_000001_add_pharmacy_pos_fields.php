<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->enum('fulfillment_type', ['in_house', 'external'])
                ->default('in_house')
                ->after('doctor_id');
        });

        $driver = Schema::connection($this->getConnection())->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->dropColumn('status');
            });

            Schema::table('prescriptions', function (Blueprint $table) {
                $table->string('status', 20)->default('pending');
            });
        } else {
            DB::statement(
                "ALTER TABLE `prescriptions` MODIFY `status` ENUM('pending', 'dispensed', 'cancelled', 'external') NOT NULL DEFAULT 'pending'"
            );
        }

        Schema::table('bills', function (Blueprint $table) {
            $table->foreignId('prescription_id')
                ->nullable()
                ->after('visit_id')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::table('bill_items', function (Blueprint $table) {
            $table->foreignId('medicine_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bill_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('medicine_id');
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prescription_id');
        });

        $driver = Schema::connection($this->getConnection())->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->dropColumn('status');
            });

            Schema::table('prescriptions', function (Blueprint $table) {
                $table->enum('status', ['pending', 'dispensed', 'cancelled'])->default('pending');
            });
        } else {
            DB::table('prescriptions')->where('status', 'external')->update(['status' => 'pending']);

            DB::statement(
                "ALTER TABLE `prescriptions` MODIFY `status` ENUM('pending', 'dispensed', 'cancelled') NOT NULL DEFAULT 'pending'"
            );
        }

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn('fulfillment_type');
        });
    }
};
