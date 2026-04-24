<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->string('name');                     // GST, Service Tax, WHT
            $table->string('code')->unique();           // GST, ST, WHT
            $table->decimal('percentage', 5, 2);        // 17.00, 5.00
            $table->boolean('is_inclusive')->default(false); // tax included in price or added on top
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('tax_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_id')->constrained()->onDelete('cascade');
            $table->string('applicable_on');            // bill_type, service_category, department
            $table->string('applicable_value');         // opd, consultation, 5 (dept id)
            $table->timestamps();

            $table->unique(['tax_id', 'applicable_on', 'applicable_value'], 'tax_mapping_unique');
        });

        // Add tax_details JSON to bills for audit trail
        Schema::table('bills', function (Blueprint $table) {
            $table->json('tax_details')->nullable()->after('tax_amount');
        });

        // Add tax_amount to bill_items for per-item tax
        Schema::table('bill_items', function (Blueprint $table) {
            $table->decimal('tax_amount', 10, 2)->default(0)->after('total_price');
        });
    }

    public function down(): void
    {
        Schema::table('bill_items', function (Blueprint $table) {
            $table->dropColumn('tax_amount');
        });
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn('tax_details');
        });
        Schema::dropIfExists('tax_mappings');
        Schema::dropIfExists('taxes');
    }
};
