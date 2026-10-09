<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acct_companies', function (Blueprint $table) {
            $table->text('sales_terms')->nullable()->after('pan');
            $table->text('purchase_terms')->nullable()->after('sales_terms');
        });

        Schema::create('acct_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('acct_branches')->restrictOnDelete();
            $table->foreignId('financial_year_id')->constrained('acct_financial_years')->restrictOnDelete();
            $table->string('number', 32);
            $table->date('order_date');
            $table->foreignId('supplier_ledger_id')->constrained('acct_ledgers')->restrictOnDelete();
            $table->string('reference_number')->nullable();
            $table->string('narration', 1000)->nullable();
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2);
            $table->string('status', 20)->default('open');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'financial_year_id', 'number'], 'purchase_orders_number_unique');
        });

        Schema::create('acct_purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('acct_purchase_orders')->cascadeOnDelete();
            $table->unsignedInteger('line_number');
            $table->string('item_name');
            $table->foreignId('product_id')->nullable()->constrained('acct_products')->nullOnDelete();
            $table->foreignId('tax_rate_id')->nullable()->constrained('acct_tax_rates')->nullOnDelete();
            $table->foreignId('hsn_sac_id')->nullable()->constrained('acct_hsn_sacs')->nullOnDelete();
            $table->decimal('quantity', 15, 3);
            $table->decimal('rate', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('taxable_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);
            $table->decimal('igst_amount', 15, 2)->default(0);
            $table->decimal('cess_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acct_purchase_order_lines');
        Schema::dropIfExists('acct_purchase_orders');

        Schema::table('acct_companies', function (Blueprint $table) {
            $table->dropColumn(['sales_terms', 'purchase_terms']);
        });
    }
};
