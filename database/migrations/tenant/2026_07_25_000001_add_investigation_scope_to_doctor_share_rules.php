<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_share_rules', function (Blueprint $table) {
            $table->enum('investigation_scope', ['all', 'lab', 'imaging'])
                ->default('all')
                ->after('investigation_id');
        });
    }

    public function down(): void
    {
        Schema::table('doctor_share_rules', function (Blueprint $table) {
            $table->dropColumn('investigation_scope');
        });
    }
};
