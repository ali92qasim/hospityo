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
            // SQLite enum CHECK blocks new values — recreate as free string.
            Schema::table('payments', function (Blueprint $table) {
                $table->string('payment_method_new', 30)->default('cash');
            });

            DB::table('payments')->update([
                'payment_method_new' => DB::raw('payment_method'),
            ]);

            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('payment_method');
            });

            Schema::table('payments', function (Blueprint $table) {
                $table->renameColumn('payment_method_new', 'payment_method');
            });
        } else {
            DB::statement("ALTER TABLE `payments` MODIFY `payment_method` ENUM('cash', 'card', 'upi', 'bank_transfer', 'cheque', 'insurance', 'advance') NOT NULL DEFAULT 'cash'");
        }

        Schema::table('admissions', function (Blueprint $table) {
            if (! Schema::hasColumn('admissions', 'refund_amount')) {
                $table->decimal('refund_amount', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('admissions', 'refund_method')) {
                $table->string('refund_method', 30)->nullable();
            }
            if (! Schema::hasColumn('admissions', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable();
            }
            if (! Schema::hasColumn('admissions', 'refunded_by')) {
                $table->unsignedBigInteger('refunded_by')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            foreach (['refund_amount', 'refund_method', 'refunded_at', 'refunded_by'] as $column) {
                if (Schema::hasColumn('admissions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        $driver = Schema::connection($this->getConnection())->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        DB::table('payments')->where('payment_method', 'advance')->update(['payment_method' => 'cash']);
        DB::statement("ALTER TABLE `payments` MODIFY `payment_method` ENUM('cash', 'card', 'upi', 'bank_transfer', 'cheque', 'insurance') NOT NULL DEFAULT 'cash'");
    }
};
