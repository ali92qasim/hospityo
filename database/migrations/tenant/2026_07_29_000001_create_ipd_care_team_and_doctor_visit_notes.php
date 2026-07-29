<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ipd_care_team')) {
            Schema::create('ipd_care_team', function (Blueprint $table) {
                $table->id();
                $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
                $table->foreignId('doctor_id')->constrained()->restrictOnDelete();
                $table->boolean('is_primary')->default(false);
                $table->timestamp('added_at');
                $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('removed_at')->nullable();
                $table->timestamps();

                $table->index(['visit_id', 'removed_at']);
                $table->index(['doctor_id', 'removed_at']);
            });
        }

        if (! Schema::hasTable('ipd_doctor_visit_notes')) {
            Schema::create('ipd_doctor_visit_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
                $table->foreignId('doctor_id')->constrained()->restrictOnDelete();
                $table->text('notes')->nullable();
                $table->text('orders')->nullable();
                $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
                $table->timestamp('visited_at');
                $table->foreignId('created_by')->constrained('users');
                $table->timestamps();

                $table->index('visit_id');
                $table->index(['doctor_id', 'visited_at']);
            });
        }

        $this->addPartialUniqueIndexes();

        $this->backfillCareTeam();
        $this->backfillDoctorVisitNotes();
        $this->ensureConsultantsOnCareTeam();

        if (Schema::hasTable('ipd_duty_doctors')) {
            Schema::rename('ipd_duty_doctors', 'ipd_duty_doctors_deprecated');
        }

        if (Schema::hasTable('ipd_consultant_visits')) {
            Schema::rename('ipd_consultant_visits', 'ipd_consultant_visits_deprecated');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ipd_consultant_visits_deprecated')) {
            Schema::rename('ipd_consultant_visits_deprecated', 'ipd_consultant_visits');
        }

        if (Schema::hasTable('ipd_duty_doctors_deprecated')) {
            Schema::rename('ipd_duty_doctors_deprecated', 'ipd_duty_doctors');
        }

        Schema::dropIfExists('ipd_doctor_visit_notes');
        Schema::dropIfExists('ipd_care_team');
    }

    private function addPartialUniqueIndexes(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            if (! $this->hasIndex('ipd_care_team', 'ipd_care_team_visit_doctor_active_unique')) {
                DB::statement('CREATE UNIQUE INDEX ipd_care_team_visit_doctor_active_unique ON ipd_care_team (visit_id, doctor_id) WHERE removed_at IS NULL');
            }

            if (! $this->hasIndex('ipd_care_team', 'ipd_care_team_visit_primary_active_unique')) {
                DB::statement('CREATE UNIQUE INDEX ipd_care_team_visit_primary_active_unique ON ipd_care_team (visit_id) WHERE removed_at IS NULL AND is_primary = 1');
            }

            return;
        }

        if ($driver === 'pgsql') {
            if (! $this->hasIndex('ipd_care_team', 'ipd_care_team_visit_doctor_active_unique')) {
                DB::statement('CREATE UNIQUE INDEX ipd_care_team_visit_doctor_active_unique ON ipd_care_team (visit_id, doctor_id) WHERE removed_at IS NULL');
            }

            if (! $this->hasIndex('ipd_care_team', 'ipd_care_team_visit_primary_active_unique')) {
                DB::statement('CREATE UNIQUE INDEX ipd_care_team_visit_primary_active_unique ON ipd_care_team (visit_id) WHERE removed_at IS NULL AND is_primary = true');
            }

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            if (! Schema::hasColumn('ipd_care_team', 'active_membership_key')) {
                DB::statement('ALTER TABLE ipd_care_team ADD COLUMN active_membership_key VARCHAR(64) GENERATED ALWAYS AS (IF(`removed_at` IS NULL, CONCAT(`visit_id`, CHAR(58), `doctor_id`), NULL)) VIRTUAL');
            }

            if (! $this->hasIndex('ipd_care_team', 'ipd_care_team_visit_doctor_active_unique')) {
                DB::statement('CREATE UNIQUE INDEX ipd_care_team_visit_doctor_active_unique ON ipd_care_team (active_membership_key)');
            }

            if (! Schema::hasColumn('ipd_care_team', 'active_primary_key')) {
                DB::statement('ALTER TABLE ipd_care_team ADD COLUMN active_primary_key BIGINT UNSIGNED GENERATED ALWAYS AS (IF(`removed_at` IS NULL AND `is_primary` = 1, `visit_id`, NULL)) VIRTUAL');
            }

            if (! $this->hasIndex('ipd_care_team', 'ipd_care_team_visit_primary_active_unique')) {
                DB::statement('CREATE UNIQUE INDEX ipd_care_team_visit_primary_active_unique ON ipd_care_team (active_primary_key)');
            }
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $driver = Schema::getConnection()->getDriverName();
        $database = Schema::getConnection()->getDatabaseName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $result = DB::select(
                'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
                [$database, $table, $indexName]
            );

            return $result !== [];
        }

        if ($driver === 'pgsql') {
            $result = DB::select(
                'SELECT 1 FROM pg_indexes WHERE schemaname = current_schema() AND tablename = ? AND indexname = ? LIMIT 1',
                [$table, $indexName]
            );

            return $result !== [];
        }

        if ($driver === 'sqlite') {
            $result = DB::select("SELECT 1 FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND name = ? LIMIT 1", [$table, $indexName]);

            return $result !== [];
        }

        return false;
    }

    private function backfillCareTeam(): void
    {
        $now = now();

        $visits = DB::table('visits')
            ->whereNotNull('doctor_id')
            ->get(['id', 'doctor_id', 'visit_datetime', 'created_at']);

        foreach ($visits as $visit) {
            if ($this->hasActiveCareTeamMember((int) $visit->id, (int) $visit->doctor_id)) {
                continue;
            }

            DB::table('ipd_care_team')->insert([
                'visit_id'    => $visit->id,
                'doctor_id'   => $visit->doctor_id,
                'is_primary'  => true,
                'added_at'    => $visit->visit_datetime ?? $visit->created_at ?? $now,
                'added_by'    => null,
                'removed_at'  => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }

        if (! Schema::hasTable('ipd_duty_doctors')) {
            return;
        }

        $dutyDoctors = DB::table('ipd_duty_doctors')->get();

        foreach ($dutyDoctors as $row) {
            $visitPrimaryDoctorId = DB::table('visits')
                ->where('id', $row->visit_id)
                ->value('doctor_id');

            if ((int) $visitPrimaryDoctorId === (int) $row->doctor_id) {
                continue;
            }

            if ($this->hasActiveCareTeamMember((int) $row->visit_id, (int) $row->doctor_id)) {
                continue;
            }

            DB::table('ipd_care_team')->insert([
                'visit_id'    => $row->visit_id,
                'doctor_id'   => $row->doctor_id,
                'is_primary'  => false,
                'added_at'    => $row->created_at ?? $now,
                'added_by'    => $row->assigned_by,
                'removed_at'  => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }
    }

    private function backfillDoctorVisitNotes(): void
    {
        if (! Schema::hasTable('ipd_consultant_visits')) {
            return;
        }

        $now = now();
        $rows = DB::table('ipd_consultant_visits')->get();

        foreach ($rows as $row) {
            $visitedAt = $row->consultant_seen_at ?? $row->created_at ?? $now;

            $exists = DB::table('ipd_doctor_visit_notes')
                ->where('visit_id', $row->visit_id)
                ->where('doctor_id', $row->consultant_doctor_id)
                ->where('visited_at', $visitedAt)
                ->where('created_by', $row->recorded_by)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('ipd_doctor_visit_notes')->insert([
                'visit_id'    => $row->visit_id,
                'doctor_id'   => $row->consultant_doctor_id,
                'notes'       => $row->visit_notes,
                'orders'      => $row->orders,
                'status'      => $row->status,
                'visited_at'  => $visitedAt,
                'created_by'  => $row->recorded_by,
                'created_at'  => $row->created_at ?? $now,
                'updated_at'  => $row->updated_at ?? $now,
            ]);
        }
    }

    private function ensureConsultantsOnCareTeam(): void
    {
        $now = now();

        $consultantPairs = DB::table('ipd_doctor_visit_notes')
            ->select('visit_id', 'doctor_id')
            ->distinct()
            ->get();

        foreach ($consultantPairs as $pair) {
            if ($this->hasActiveCareTeamMember((int) $pair->visit_id, (int) $pair->doctor_id)) {
                continue;
            }

            DB::table('ipd_care_team')->insert([
                'visit_id'    => $pair->visit_id,
                'doctor_id'   => $pair->doctor_id,
                'is_primary'  => false,
                'added_at'    => $now,
                'added_by'    => null,
                'removed_at'  => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }
    }

    private function hasActiveCareTeamMember(int $visitId, int $doctorId): bool
    {
        return DB::table('ipd_care_team')
            ->where('visit_id', $visitId)
            ->where('doctor_id', $doctorId)
            ->whereNull('removed_at')
            ->exists();
    }
};
