<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add manage_stock column if it doesn't exist
        if (!Schema::hasColumn('medicines', 'manage_stock')) {
            Schema::table('medicines', function (Blueprint $table) {
                $table->boolean('manage_stock')->default(true)->after('status');
            });
        }

        // For SQLite, we need to recreate the table to change column type to enum
        if (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't support ENUM, so we'll use CHECK constraint
            DB::statement('DROP TABLE IF EXISTS medicines_backup');
            DB::statement('CREATE TABLE medicines_backup AS SELECT * FROM medicines');
            
            Schema::dropIfExists('medicines');
            
            Schema::create('medicines', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('generic_name')->nullable();
                $table->foreignId('brand_id')->nullable()->constrained('medicine_brands')->onDelete('set null');
                $table->foreignId('category_id')->nullable()->constrained('medicine_categories')->onDelete('set null');
                $table->enum('dosage_form', [
                    'tablet',
                    'capsule',
                    'syrup',
                    'suspension',
                    'injection',
                    'cream',
                    'ointment',
                    'gel',
                    'drops',
                    'inhaler',
                    'powder',
                    'solution',
                    'lotion',
                    'spray',
                    'patch'
                ])->nullable();
                $table->string('strength')->nullable();
                $table->foreignId('base_unit_id')->nullable()->constrained('units')->onDelete('set null');
                $table->foreignId('purchase_unit_id')->nullable()->constrained('units')->onDelete('set null');
                $table->foreignId('dispensing_unit_id')->nullable()->constrained('units')->onDelete('set null');
                $table->integer('reorder_level')->default(10);
                $table->string('manufacturer')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->boolean('manage_stock')->default(true);
                $table->timestamps();
            });
            
            // Insert data back with manage_stock default value
            DB::statement('INSERT INTO medicines (id, name, generic_name, brand_id, category_id, dosage_form, strength, base_unit_id, purchase_unit_id, dispensing_unit_id, reorder_level, manufacturer, status, manage_stock, created_at, updated_at) 
                SELECT id, name, generic_name, brand_id, category_id, dosage_form, strength, base_unit_id, purchase_unit_id, dispensing_unit_id, reorder_level, manufacturer, status, COALESCE(manage_stock, 1), created_at, updated_at FROM medicines_backup');
            DB::statement('DROP TABLE medicines_backup');
        } else {
            // For MySQL/PostgreSQL
            DB::statement("ALTER TABLE medicines MODIFY COLUMN dosage_form ENUM('tablet', 'capsule', 'syrup', 'suspension', 'injection', 'cream', 'ointment', 'gel', 'drops', 'inhaler', 'powder', 'solution', 'lotion', 'spray', 'patch')");
        }
    }

    public function down(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->dropColumn('manage_stock');
        });
        
        // Revert dosage_form back to string
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE medicines MODIFY COLUMN dosage_form VARCHAR(255)");
        }
    }
};
