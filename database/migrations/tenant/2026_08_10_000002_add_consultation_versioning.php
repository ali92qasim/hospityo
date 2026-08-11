<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->boolean('is_current')->default(true)->after('visit_id');
            $table->timestamp('superseded_at')->nullable()->after('is_current');
        });

        $duplicateVisitIds = DB::table('consultations')
            ->select('visit_id')
            ->groupBy('visit_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('visit_id');

        foreach ($duplicateVisitIds as $visitId) {
            $latestId = DB::table('consultations')
                ->where('visit_id', $visitId)
                ->orderByDesc('id')
                ->value('id');

            DB::table('consultations')
                ->where('visit_id', $visitId)
                ->where('id', '!=', $latestId)
                ->update([
                    'is_current' => false,
                    'superseded_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn(['is_current', 'superseded_at']);
        });
    }
};
