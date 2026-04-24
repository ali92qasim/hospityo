<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('landlord')->create('tenant_users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('login_token')->nullable();
            $table->timestamps();

            $table->unique(['email', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('tenant_users');
    }
};
