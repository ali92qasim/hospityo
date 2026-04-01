<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->dropColumn(['stock_quantity', 'expiry_date', 'batch_number', 'unit_price']);
        });
    }

    public function down()
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->integer('stock_quantity')->default(0);
            $table->date('expiry_date')->nullable();
            $table->string('batch_number')->nullable();
        });
    }
};