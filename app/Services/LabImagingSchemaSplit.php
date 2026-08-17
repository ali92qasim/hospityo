<?php

namespace App\Services;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class LabImagingSchemaSplit
{
    public function run(): void
    {
        $schema = Schema::connection('tenant');
        $db = DB::connection('tenant');

        if ($schema->hasTable('lab_tests') && ! $schema->hasTable('investigations')) {
            $this->ensureBillingColumns($schema, $db);
            $this->ensureDoctorShareColumns($schema, $db);
            $this->ensureTaxMappings($db);
            $this->ensureGlAccounts($db);

            return;
        }

        if (! $schema->hasTable('investigations')) {
            $this->ensureGlAccounts($db);

            return;
        }

        $this->assertNoMixedKindOrders($db);
        $this->withoutForeignKeyChecks($db, function () use ($schema, $db) {
            $this->createImagingTables($schema);
            $this->copyImagingData($db);
            $this->deleteImagingFromLegacyTables($db);
            $this->renameLabTables($schema);
            $this->repointDependents($schema, $db);
            $this->dropLegacyTables($schema);
        });

        $this->ensureBillingColumns($schema, $db);
        $this->ensureDoctorShareColumns($schema, $db);
        $this->ensureTaxMappings($db);
        $this->ensureGlAccounts($db);
    }

    private function assertNoMixedKindOrders($db): void
    {
        if (! Schema::connection('tenant')->hasTable('investigation_order_items')) {
            return;
        }

        $mixed = $db->table('investigation_order_items as items')
            ->join('investigations', 'investigations.id', '=', 'items.investigation_id')
            ->select('items.investigation_order_id')
            ->groupBy('items.investigation_order_id')
            ->havingRaw('COUNT(DISTINCT investigations.kind) > 1')
            ->pluck('investigation_order_id')
            ->all();

        if ($mixed !== []) {
            throw new RuntimeException(
                'Cannot split lab/imaging schema; mixed-kind orders found: '.implode(', ', $mixed)
            );
        }
    }

    private function createImagingTables($schema): void
    {
        if (! $schema->hasTable('imaging_studies')) {
            $schema->create('imaging_studies', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('category', 50);
                $table->decimal('price', 10, 2);
                $table->string('turnaround_time', 100)->nullable();
                $table->text('instructions')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('imaging_orders')) {
            $schema->create('imaging_orders', function (Blueprint $table) {
                $table->id();
                $table->string('order_number')->unique();
                $table->string('share_token', 64)->nullable()->unique();
                $table->unsignedBigInteger('patient_id');
                $table->unsignedBigInteger('visit_id')->nullable();
                $table->unsignedBigInteger('doctor_id')->nullable();
                $table->string('priority', 20)->default('routine');
                $table->string('status', 30)->default('ordered');
                $table->timestamp('ordered_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('clinical_notes')->nullable();
                $table->text('special_instructions')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('imaging_order_items')) {
            $schema->create('imaging_order_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('imaging_order_id');
                $table->unsignedBigInteger('imaging_study_id');
                $table->unsignedSmallInteger('quantity')->default(1);
                $table->string('priority', 20)->default('routine');
                $table->string('status', 30)->default('ordered');
                $table->text('clinical_notes')->nullable();
                $table->string('test_location', 20)->default('outdoor');
                $table->timestamps();
                $table->unique(['imaging_order_id', 'imaging_study_id'], 'img_oi_unique');
            });
        }

        if (! $schema->hasTable('imaging_reports')) {
            $schema->create('imaging_reports', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('imaging_order_id');
                $table->longText('report_text')->nullable();
                $table->text('impression')->nullable();
                $table->string('file_path')->nullable();
                $table->string('status', 20)->default('draft');
                $table->unsignedBigInteger('radiologist_id')->nullable();
                $table->timestamp('reported_at')->nullable();
                $table->timestamps();
            });
        }
    }

    private function copyImagingData($db): void
    {
        foreach ($db->table('investigations')->where('kind', 'imaging')->orderBy('id')->get() as $row) {
            $db->table('imaging_studies')->insert($this->catalogPayload($row, includeSampleType: false));
        }

        $imagingOrderIds = $db->table('investigation_orders')->where('kind', 'imaging')->pluck('id')->all();

        if ($imagingOrderIds === []) {
            return;
        }

        foreach ($db->table('investigation_orders')->whereIn('id', $imagingOrderIds)->orderBy('id')->get() as $row) {
            $db->table('imaging_orders')->insert($this->orderPayload($row, includeSample: false));
        }

        if (Schema::connection('tenant')->hasTable('investigation_order_items')) {
            foreach ($db->table('investigation_order_items')->whereIn('investigation_order_id', $imagingOrderIds)->orderBy('id')->get() as $row) {
                $db->table('imaging_order_items')->insert($this->itemPayload($row, 'imaging_order_id', 'imaging_study_id'));
            }
        }

        if (Schema::connection('tenant')->hasTable('radiology_results') && $imagingOrderIds !== []) {
            foreach ($db->table('radiology_results')->whereIn('investigation_order_id', $imagingOrderIds)->orderBy('id')->get() as $row) {
                $db->table('imaging_reports')->insert([
                    'id' => $row->id,
                    'imaging_order_id' => $row->investigation_order_id,
                    'report_text' => $row->report_text,
                    'impression' => $row->impression,
                    'file_path' => $row->file_path,
                    'status' => $row->status,
                    'radiologist_id' => $row->radiologist_id,
                    'reported_at' => $row->reported_at,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        }
    }

    private function deleteImagingFromLegacyTables($db): void
    {
        $imagingOrderIds = $db->table('investigation_orders')->where('kind', 'imaging')->pluck('id')->all();
        $imagingInvestigationIds = $db->table('investigations')->where('kind', 'imaging')->pluck('id')->all();

        if (Schema::connection('tenant')->hasTable('radiology_results') && $imagingOrderIds !== []) {
            $db->table('radiology_results')->whereIn('investigation_order_id', $imagingOrderIds)->delete();
        }

        if (Schema::connection('tenant')->hasTable('investigation_order_items') && $imagingOrderIds !== []) {
            $db->table('investigation_order_items')->whereIn('investigation_order_id', $imagingOrderIds)->delete();
        }

        if ($imagingOrderIds !== []) {
            $db->table('investigation_orders')->whereIn('id', $imagingOrderIds)->delete();
        }

        if ($imagingInvestigationIds !== []) {
            $db->table('investigations')->whereIn('id', $imagingInvestigationIds)->delete();
        }
    }

    private function renameLabTables($schema): void
    {
        if ($schema->hasTable('investigations') && ! $schema->hasTable('lab_tests')) {
            $schema->rename('investigations', 'lab_tests');
        }

        if ($schema->hasTable('investigation_orders') && ! $schema->hasTable('lab_orders')) {
            $schema->rename('investigation_orders', 'lab_orders');
        }

        if ($schema->hasTable('investigation_order_items') && ! $schema->hasTable('lab_order_items')) {
            $schema->rename('investigation_order_items', 'lab_order_items');
        }

        if ($schema->hasTable('lab_tests') && $schema->hasColumn('lab_tests', 'kind')) {
            $schema->table('lab_tests', function (Blueprint $table) {
                $table->dropColumn('kind');
            });
        }

        if ($schema->hasTable('lab_orders') && $schema->hasColumn('lab_orders', 'kind')) {
            $schema->table('lab_orders', function (Blueprint $table) {
                $table->dropColumn('kind');
            });
        }

        if ($schema->hasTable('lab_order_items')) {
            $this->renameColumn($schema, 'lab_order_items', 'investigation_order_id', 'lab_order_id');
            $this->renameColumn($schema, 'lab_order_items', 'investigation_id', 'lab_test_id');
        }
    }

    private function catalogPayload(object $row, bool $includeSampleType): array
    {
        $payload = [
            'id' => $row->id,
            'code' => $row->code,
            'name' => $row->name,
            'description' => $row->description ?? null,
            'category' => $row->category,
            'price' => $row->price,
            'turnaround_time' => $row->turnaround_time ?? null,
            'instructions' => $row->instructions ?? null,
            'is_active' => $row->is_active ?? true,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
        ];

        if ($includeSampleType) {
            $payload['sample_type'] = $row->sample_type ?? 'other';
        }

        return $payload;
    }

    private function orderPayload(object $row, bool $includeSample): array
    {
        $payload = [
            'id' => $row->id,
            'order_number' => $row->order_number,
            'share_token' => $row->share_token ?? null,
            'patient_id' => $row->patient_id,
            'visit_id' => $row->visit_id ?? null,
            'doctor_id' => $row->doctor_id,
            'priority' => $row->priority ?? 'routine',
            'status' => $row->status ?? 'ordered',
            'ordered_at' => $row->ordered_at ?? null,
            'completed_at' => $row->completed_at ?? null,
            'clinical_notes' => $row->clinical_notes ?? null,
            'special_instructions' => $row->special_instructions ?? null,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
        ];

        if ($includeSample) {
            $payload['sample_collected_at'] = $row->sample_collected_at ?? null;
        }

        return $payload;
    }

    private function itemPayload(object $row, string $orderFk, string $catalogFk): array
    {
        return [
            'id' => $row->id,
            $orderFk => $row->investigation_order_id,
            $catalogFk => $row->investigation_id,
            'quantity' => $row->quantity ?? 1,
            'priority' => $row->priority ?? 'routine',
            'status' => $row->status ?? 'ordered',
            'clinical_notes' => $row->clinical_notes ?? null,
            'test_location' => $row->test_location ?? 'outdoor',
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
        ];
    }

    private function repointDependents($schema, $db): void
    {
        if ($schema->hasTable('lab_samples') && $schema->hasColumn('lab_samples', 'investigation_order_id')) {
            $this->renameColumn($schema, 'lab_samples', 'investigation_order_id', 'lab_order_id');
        }

        if ($schema->hasTable('lab_results') && $schema->hasColumn('lab_results', 'investigation_order_id')) {
            $this->renameColumn($schema, 'lab_results', 'investigation_order_id', 'lab_order_id');
        }
    }

    private function dropLegacyTables($schema): void
    {
        $schema->dropIfExists('radiology_results');
        $schema->dropIfExists('investigation_order_items');
        $schema->dropIfExists('investigation_orders');
        $schema->dropIfExists('investigations');
    }

    private function ensureBillingColumns($schema, $db): void
    {
        if (! $schema->hasTable('bill_items')) {
            return;
        }

        if (! $schema->hasColumn('bill_items', 'lab_test_id')) {
            $schema->table('bill_items', function (Blueprint $table) {
                $table->unsignedBigInteger('lab_test_id')->nullable();
            });
        }

        if (! $schema->hasColumn('bill_items', 'imaging_study_id')) {
            $schema->table('bill_items', function (Blueprint $table) {
                $table->unsignedBigInteger('imaging_study_id')->nullable();
            });
        }

        if ($schema->hasColumn('bill_items', 'investigation_id')) {
            $items = $db->table('bill_items')->whereNotNull('investigation_id')->get(['id', 'investigation_id', 'item_category']);
            $unclassified = [];

            foreach ($items as $item) {
                $labExists = $db->table('lab_tests')->where('id', $item->investigation_id)->exists();
                $imagingExists = $db->table('imaging_studies')->where('id', $item->investigation_id)->exists();

                if ($labExists) {
                    $db->table('bill_items')->where('id', $item->id)->update([
                        'lab_test_id' => $item->investigation_id,
                        'item_category' => 'lab',
                    ]);
                } elseif ($imagingExists) {
                    $db->table('bill_items')->where('id', $item->id)->update([
                        'imaging_study_id' => $item->investigation_id,
                        'item_category' => 'imaging',
                    ]);
                } elseif (($item->item_category ?? null) === 'investigation') {
                    $unclassified[] = $item->id;
                }
            }

            if ($unclassified !== []) {
                throw new RuntimeException(
                    'Cannot classify bill_items.investigation_id for ids: '.implode(', ', $unclassified)
                );
            }

            $db->table('bill_items')->where('item_category', 'investigation')->update(['item_category' => 'lab']);

            $this->dropConstrainedColumn($schema, 'bill_items', 'investigation_id');
        }
    }

    private function ensureDoctorShareColumns($schema, $db): void
    {
        if (! $schema->hasTable('doctor_share_rules')) {
            return;
        }

        if (! $schema->hasColumn('doctor_share_rules', 'lab_test_id')) {
            $schema->table('doctor_share_rules', function (Blueprint $table) {
                $table->unsignedBigInteger('lab_test_id')->nullable();
            });
        }

        if (! $schema->hasColumn('doctor_share_rules', 'imaging_study_id')) {
            $schema->table('doctor_share_rules', function (Blueprint $table) {
                $table->unsignedBigInteger('imaging_study_id')->nullable();
            });
        }

        if ($schema->hasColumn('doctor_share_rules', 'investigation_id')) {
            foreach ($db->table('doctor_share_rules')->whereNotNull('investigation_id')->get() as $rule) {
                $labExists = $db->table('lab_tests')->where('id', $rule->investigation_id)->exists();
                $imagingExists = $db->table('imaging_studies')->where('id', $rule->investigation_id)->exists();

                $db->table('doctor_share_rules')->where('id', $rule->id)->update([
                    'lab_test_id' => $labExists ? $rule->investigation_id : null,
                    'imaging_study_id' => $imagingExists ? $rule->investigation_id : null,
                ]);
            }

            $this->dropIndexIfExists($schema, 'doctor_share_rules', 'dsr_investigation_active_idx');
            $this->dropIndexIfExists($schema, 'doctor_share_rules', 'dsr_unique_rule');
            $this->dropConstrainedColumn($schema, 'doctor_share_rules', 'investigation_id');
        }

        $investigationRules = $db->table('doctor_share_rules')->where('applies_to', 'investigation')->get();

        foreach ($investigationRules as $rule) {
            $base = (array) $rule;
            unset($base['id']);

            $labCopy = $base;
            $labCopy['applies_to'] = 'lab';
            $labCopy['created_at'] = now();
            $labCopy['updated_at'] = now();

            $imagingCopy = $base;
            $imagingCopy['applies_to'] = 'imaging';
            $imagingCopy['created_at'] = now();
            $imagingCopy['updated_at'] = now();

            $db->table('doctor_share_rules')->insert($labCopy);
            $db->table('doctor_share_rules')->insert($imagingCopy);
            $db->table('doctor_share_rules')->where('id', $rule->id)->delete();
        }
    }

    private function ensureTaxMappings($db): void
    {
        if (! Schema::connection('tenant')->hasTable('tax_mappings')) {
            return;
        }

        $rows = $db->table('tax_mappings')->where('applicable_value', 'investigation')->get();

        foreach ($rows as $row) {
            foreach (['lab', 'imaging'] as $value) {
                $exists = $db->table('tax_mappings')
                    ->where('tax_id', $row->tax_id)
                    ->where('applicable_on', $row->applicable_on)
                    ->where('applicable_value', $value)
                    ->exists();

                if (! $exists) {
                    $db->table('tax_mappings')->insert([
                        'tax_id' => $row->tax_id,
                        'applicable_on' => $row->applicable_on,
                        'applicable_value' => $value,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $db->table('tax_mappings')->where('id', $row->id)->delete();
        }
    }

    private function ensureGlAccounts($db): void
    {
        if (! Schema::connection('tenant')->hasTable('accounts')) {
            return;
        }

        $db->table('accounts')->where('code', '4300')->update(['name' => 'Lab Revenue']);

        if (! $db->table('accounts')->where('code', '4310')->exists()) {
            $db->table('accounts')->insert([
                'code' => '4310',
                'name' => 'Imaging Revenue',
                'type' => 'revenue',
                'is_system' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function dropIndexIfExists($schema, string $table, string $index): void
    {
        try {
            $schema->table($table, function (Blueprint $blueprint) use ($index) {
                $blueprint->dropIndex($index);
            });
        } catch (\Throwable) {
            try {
                $schema->table($table, function (Blueprint $blueprint) use ($index) {
                    $blueprint->dropUnique($index);
                });
            } catch (\Throwable) {
            }
        }
    }

    private function dropConstrainedColumn($schema, string $table, string $column): void
    {
        if (! $schema->hasColumn($table, $column)) {
            return;
        }

        $db = DB::connection('tenant');

        foreach ([$column, $table.'_'.$column.'_foreign'] as $index) {
            try {
                $schema->table($table, function (Blueprint $blueprint) use ($index, $column) {
                    if ($index === $column) {
                        $blueprint->dropForeign([$column]);
                    } else {
                        $blueprint->dropForeign($index);
                    }
                });
                break;
            } catch (\Throwable) {
                continue;
            }
        }

        $this->withoutForeignKeyChecks($db, function () use ($schema, $table, $column) {
            if ($schema->hasColumn($table, $column)) {
                $schema->table($table, function (Blueprint $blueprint) use ($column) {
                    $blueprint->dropColumn($column);
                });
            }
        });
    }
    private function renameColumn($schema, string $table, string $from, string $to): void
    {
        if (! $schema->hasColumn($table, $from) || $schema->hasColumn($table, $to)) {
            return;
        }

        $schema->table($table, function (Blueprint $blueprint) use ($from, $to) {
            $blueprint->renameColumn($from, $to);
        });
    }

    private function withoutForeignKeyChecks($db, callable $callback): void
    {
        $driver = $db->getDriverName();

        if ($driver === 'mysql') {
            $db->statement('SET FOREIGN_KEY_CHECKS=0');
        } elseif ($driver === 'sqlite') {
            $db->statement('PRAGMA foreign_keys = OFF');
        }

        try {
            $callback();
        } finally {
            if ($driver === 'mysql') {
                $db->statement('SET FOREIGN_KEY_CHECKS=1');
            } elseif ($driver === 'sqlite') {
                $db->statement('PRAGMA foreign_keys = ON');
            }
        }
    }
}
