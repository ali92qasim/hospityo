<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Migrate any existing cache-based settings into the new table
        // This preserves settings for live tenants during deployment
        $settingKeys = [
            'hospital_name', 'hospital_address', 'hospital_phone',
            'hospital_email', 'hospital_logo', 'currency', 'timezone',
            'date_format', 'time_format',
        ];

        $now = now()->toDateTimeString();

        foreach ($settingKeys as $key) {
            $value = cache("settings.{$key}");
            if ($value !== null) {
                \Illuminate\Support\Facades\DB::table('tenant_settings')->insert([
                    'key'        => $key,
                    'value'      => $value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
