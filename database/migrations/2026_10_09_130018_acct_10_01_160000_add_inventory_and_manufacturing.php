<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acct_companies', function (Blueprint $table) {
            $table->boolean('allow_negative_stock')->default(false)->after('tax_rounding');
        });

        Schema::table('acct_products', function (Blueprint $table) {
            $table->decimal('maximum_stock', 15, 4)->nullable()->after('reorder_level');
            $table->boolean('track_batch')->default(false)->after('maximum_stock');
            $table->boolean('track_serial')->default(false)->after('track_batch');
        });

        Schema::create('acct_stock_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('acct_products')->restrictOnDelete();
            $table->string('batch_number', 40);
            $table->date('manufactured_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'product_id', 'batch_number'], 'stock_batches_number_unique');
        });

        Schema::create('acct_stock_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('acct_products')->restrictOnDelete();
            $table->string('serial_number', 60);
            $table->string('status', 12)->default('available');
            $table->timestamps();

            $table->unique(['company_id', 'serial_number']);
        });

        Schema::table('acct_stock_movements', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable()->after('godown_id')->constrained('acct_stock_batches')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->after('batch_id')->constrained('acct_stock_serials')->nullOnDelete();
        });

        Schema::table('acct_stock_transaction_lines', function (Blueprint $table) {
            $table->string('batch_number', 40)->nullable()->after('value');
            $table->date('manufactured_on')->nullable()->after('batch_number');
            $table->date('expires_on')->nullable()->after('manufactured_on');
            $table->string('serial_number', 60)->nullable()->after('expires_on');
        });

        Schema::table('acct_invoice_lines', function (Blueprint $table) {
            $table->string('batch_number', 40)->nullable()->after('godown_id');
            $table->date('manufactured_on')->nullable()->after('batch_number');
            $table->date('expires_on')->nullable()->after('manufactured_on');
            $table->string('serial_number', 60)->nullable()->after('expires_on');
        });

        Schema::create('acct_bills_of_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->foreignId('finished_product_id')->constrained('acct_products')->restrictOnDelete();
            $table->string('name');
            $table->decimal('wastage_percent', 8, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'name']);
        });

        Schema::create('acct_bom_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_of_material_id')->constrained('acct_bills_of_materials')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('acct_products')->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('acct_units')->nullOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->decimal('wastage_percent', 8, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('acct_bom_byproducts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_of_material_id')->constrained('acct_bills_of_materials')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('acct_products')->restrictOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->timestamps();
        });

        Schema::create('acct_manufacturing_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('acct_branches')->restrictOnDelete();
            $table->foreignId('financial_year_id')->constrained('acct_financial_years')->restrictOnDelete();
            $table->foreignId('bill_of_material_id')->constrained('acct_bills_of_materials')->restrictOnDelete();
            $table->string('number', 30);
            $table->date('manufactured_on');
            $table->decimal('quantity', 15, 4);
            $table->foreignId('source_godown_id')->constrained('acct_godowns')->restrictOnDelete();
            $table->foreignId('destination_godown_id')->constrained('acct_godowns')->restrictOnDelete();
            $table->decimal('material_cost', 15, 2)->default(0);
            $table->foreignId('voucher_id')->nullable()->constrained('acct_vouchers')->nullOnDelete();
            $table->string('narration', 1000)->nullable();
            $table->string('status', 20);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'branch_id', 'financial_year_id', 'number'], 'manufacturing_orders_number_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acct_manufacturing_orders');
        Schema::dropIfExists('acct_bom_byproducts');
        Schema::dropIfExists('acct_bom_lines');
        Schema::dropIfExists('acct_bills_of_materials');

        Schema::table('acct_invoice_lines', function (Blueprint $table) {
            $table->dropColumn(['batch_number', 'manufactured_on', 'expires_on', 'serial_number']);
        });

        Schema::table('acct_stock_transaction_lines', function (Blueprint $table) {
            $table->dropColumn(['batch_number', 'manufactured_on', 'expires_on', 'serial_number']);
        });

        Schema::table('acct_stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('serial_id');
            $table->dropConstrainedForeignId('batch_id');
        });

        Schema::dropIfExists('acct_stock_serials');
        Schema::dropIfExists('acct_stock_batches');

        Schema::table('acct_products', function (Blueprint $table) {
            $table->dropColumn(['maximum_stock', 'track_batch', 'track_serial']);
        });

        Schema::table('acct_companies', function (Blueprint $table) {
            $table->dropColumn('allow_negative_stock');
        });
    }
};
