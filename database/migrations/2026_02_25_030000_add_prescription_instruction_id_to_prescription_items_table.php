<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescription_items', function (Blueprint $table) {
            $table->foreignId('prescription_instruction_id')->nullable()->after('medicine_id')->constrained('prescription_instructions')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('prescription_items', function (Blueprint $table) {
            $table->dropForeign(['prescription_instruction_id']);
            $table->dropColumn('prescription_instruction_id');
        });
    }
};
