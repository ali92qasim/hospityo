<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('landlord')->create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->string('mode')->default('sandbox'); // sandbox, live
            $table->json('credentials')->nullable();     // encrypted key-value pairs
            $table->json('config_fields');               // defines which fields this gateway needs
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('payment_gateways');
    }
};
