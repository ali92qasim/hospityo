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
            $table->unsignedBigInteger('reversed_entry_id')->nullable()->unique()->after('entry_type');
        });

        // Backfill: link existing reversal entries to their originals
        $reversals = DB::table('journal_entries')->where('entry_type', 'reversal')->whereNull('reversed_entry_id')->get();

        foreach ($reversals as $reversal) {
            $original = DB::table('journal_entries')
                ->where('reference_type', $reversal->reference_type)
                ->where('reference_id', $reversal->reference_id)
                ->where('entry_date', $reversal->entry_date)
                ->whereIn('entry_type', ['superseded', 'original'])
                ->where('id', '<', $reversal->id)
                ->orderBy('id', 'desc')
                ->first();

            if ($original) {
                $alreadyLinked = DB::table('journal_entries')
                    ->where('reversed_entry_id', $original->id)
                    ->exists();

                if (!$alreadyLinked) {
                    DB::table('journal_entries')
                        ->where('id', $reversal->id)
                        ->update(['reversed_entry_id' => $original->id]);

                    if ($original->entry_type === 'original') {
                        DB::table('journal_entries')
                            ->where('id', $original->id)
                            ->update(['entry_type' => 'superseded']);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropColumn('reversed_entry_id');
        });
    }
};
