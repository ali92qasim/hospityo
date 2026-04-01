<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();           // URL-safe identifier (used in subdomain)
            $table->string('domain')->unique();          // full subdomain e.g. acme.saasy.test
            $table->string('database')->unique();        // tenant DB name e.g. tenant_acme
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('logo')->nullable();
            $table->string('status')->default('active'); // active, inactive, suspended
            $table->json('settings')->nullable();        // tenant-level config overrides
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
