<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('landlord')->table('plans', function (Blueprint $table) {
            $table->string('paddle_price_id')->nullable()->after('trial_days');
        });

        Schema::connection('landlord')->table('subscriptions', function (Blueprint $table) {
            $table->string('gateway')->default('manual')->after('status');
            $table->string('gateway_subscription_id')->nullable()->after('gateway');
            $table->string('gateway_customer_id')->nullable()->after('gateway_subscription_id');
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->table('plans', function (Blueprint $table) {
            $table->dropColumn('paddle_price_id');
        });

        Schema::connection('landlord')->table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['gateway', 'gateway_subscription_id', 'gateway_customer_id']);
        });
    }
};
