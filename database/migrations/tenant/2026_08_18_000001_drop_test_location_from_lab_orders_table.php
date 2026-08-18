<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lab_orders')) {
            return;
        }

        $columns = collect(['test_location', 'location'])
            ->filter(fn (string $column) => Schema::hasColumn('lab_orders', $column))
            ->values()
            ->all();

        if ($columns === []) {
            return;
        }

        Schema::table('lab_orders', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('lab_orders') || Schema::hasColumn('lab_orders', 'test_location')) {
            return;
        }

        Schema::table('lab_orders', function (Blueprint $table) {
            $table->string('test_location', 20)->default('outdoor');
        });
    }
};
