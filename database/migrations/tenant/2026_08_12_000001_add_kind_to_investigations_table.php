<?php

use App\Enums\InvestigationKind;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('investigations', 'kind')) {
            Schema::table('investigations', function (Blueprint $table) {
                $table->string('kind', 20)->nullable()->after('description');
            });
        }

        $unclassified = [];

        DB::table('investigations')->orderBy('id')->each(function ($row) use (&$unclassified) {
            $normalizedCategory = strtolower(trim((string) $row->category));
            $kind = InvestigationKind::fromCategory($normalizedCategory);

            if ($kind === null) {
                $unclassified[] = "id={$row->id} code={$row->code} category={$row->category}";

                return;
            }

            DB::table('investigations')
                ->where('id', $row->id)
                ->update([
                    'kind' => $kind->value,
                    'category' => $normalizedCategory,
                ]);
        });

        if ($unclassified !== []) {
            throw new RuntimeException(
                'Cannot backfill investigations.kind for unclassified categories: '.implode('; ', $unclassified)
            );
        }

        // Avoid doctrine/dbal ->change(); drop/re-add only if still nullable is unnecessary on MySQL
        // when we can alter via raw SQL for portability across sqlite/mysql.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // SQLite test migrations already treat the column as present; enforce NOT NULL via rebuild if needed.
            // For sqlite in-memory tests, recreate is heavy — keep nullable=false via schema builder when supported.
            Schema::table('investigations', function (Blueprint $table) {
                $table->string('kind', 20)->nullable(false)->change();
            });
        } else {
            DB::statement("ALTER TABLE investigations MODIFY kind VARCHAR(20) NOT NULL");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('investigations', 'kind')) {
            Schema::table('investigations', function (Blueprint $table) {
                $table->dropColumn('kind');
            });
        }
    }
};
