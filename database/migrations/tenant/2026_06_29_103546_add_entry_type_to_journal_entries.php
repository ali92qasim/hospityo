<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->string('entry_type', 20)->default('original')->after('is_auto');
            $table->index('entry_type');
        });

        // Backfill existing rows based on description prefixes
        DB::table('journal_entries')
            ->where('description', 'like', 'REVERSAL%')
            ->update(['entry_type' => 'reversal']);

        DB::table('journal_entries')
            ->where('description', 'like', 'Overpayment%')
            ->update(['entry_type' => 'adjustment']);
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropIndex(['entry_type']);
            $table->dropColumn('entry_type');
        });
    }
};
