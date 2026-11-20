<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (! Schema::hasColumn('quotations', 'title')) {
                $table->string('title', 255)->nullable()->after('valid_until');
            }
            if (! Schema::hasColumn('quotations', 'special_instructions')) {
                $table->text('special_instructions')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('quotations', 'terms_conditions')) {
                $table->text('terms_conditions')->nullable()->after('special_instructions');
            }
            if (! Schema::hasColumn('quotations', 'company_id')) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('companies')
                    ->nullOnDelete();
            }
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            if (! Schema::hasColumn('quotation_items', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('quotation_id');
            }
            if (! Schema::hasColumn('quotation_items', 'description')) {
                $table->text('description')->nullable()->after('uom_id');
            }
            if (! Schema::hasColumn('quotation_items', 'hsn_code')) {
                $table->string('hsn_code', 32)->nullable()->after('description');
            }
            if (! Schema::hasColumn('quotation_items', 'mrp')) {
                $table->decimal('mrp', 12, 2)->nullable()->after('hsn_code');
            }
            if (! Schema::hasColumn('quotation_items', 'tax_rate')) {
                $table->decimal('tax_rate', 8, 2)->nullable()->after('mrp');
            }
            if (! Schema::hasColumn('quotation_items', 'image_path')) {
                $table->string('image_path', 500)->nullable()->after('tax_rate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            foreach (['sort_order', 'description', 'hsn_code', 'mrp', 'tax_rate', 'image_path'] as $column) {
                if (Schema::hasColumn('quotation_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('quotations', function (Blueprint $table) {
            if (Schema::hasColumn('quotations', 'company_id')) {
                $table->dropConstrainedForeignId('company_id');
            }
            foreach (['title', 'special_instructions', 'terms_conditions'] as $column) {
                if (Schema::hasColumn('quotations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
