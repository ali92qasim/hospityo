<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('abbreviation');
            $table->foreignId('base_unit_id')->nullable()->constrained('units')->onDelete('cascade');
            $table->decimal('conversion_factor', 10, 4)->default(1);
            $table->enum('type', ['solid', 'liquid', 'gas', 'packaging'])->default('solid');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('units');
    }
};