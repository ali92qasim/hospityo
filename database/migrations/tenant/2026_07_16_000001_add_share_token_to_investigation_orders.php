<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investigation_orders', function (Blueprint $table) {
            $table->string('share_token', 64)->nullable()->unique()->after('order_number');
        });

        $orders = DB::table('investigation_orders')->whereNull('share_token')->pluck('id');

        foreach ($orders as $id) {
            DB::table('investigation_orders')
                ->where('id', $id)
                ->update(['share_token' => Str::random(40)]);
        }
    }

    public function down(): void
    {
        Schema::table('investigation_orders', function (Blueprint $table) {
            $table->dropUnique(['share_token']);
            $table->dropColumn('share_token');
        });
    }
};
