<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acct_tax_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->string('name');
            $table->string('code', 32)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'name']);
            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('acct_tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->foreignId('tax_category_id')->constrained('acct_tax_categories')->restrictOnDelete();
            $table->string('name');
            $table->string('code', 32)->nullable();
            $table->decimal('cgst_rate', 8, 4)->default(0);
            $table->decimal('sgst_rate', 8, 4)->default(0);
            $table->decimal('igst_rate', 8, 4)->default(0);
            $table->decimal('cess_rate', 8, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'name']);
            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'tax_category_id']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('acct_hsn_sacs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->foreignId('tax_rate_id')->nullable()->constrained('acct_tax_rates')->restrictOnDelete();
            $table->string('code', 16);
            $table->string('kind', 8);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('acct_tax_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->string('component', 8);
            $table->foreignId('ledger_id')->constrained('acct_ledgers')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'component']);
            $table->index(['company_id', 'ledger_id']);
        });

        Schema::create('acct_stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('acct_branches')->restrictOnDelete();
            $table->foreignId('financial_year_id')->constrained('acct_financial_years')->restrictOnDelete();
            $table->string('type', 20);
            $table->string('number', 30);
            $table->date('transaction_date');
            $table->foreignId('source_godown_id')->nullable()->constrained('acct_godowns')->restrictOnDelete();
            $table->foreignId('destination_godown_id')->nullable()->constrained('acct_godowns')->restrictOnDelete();
            $table->string('narration', 1000)->nullable();
            $table->string('status', 20);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['company_id', 'branch_id', 'financial_year_id', 'type', 'number'],
                'stock_transactions_number_unique'
            );
            $table->index(['company_id', 'branch_id', 'financial_year_id', 'transaction_date'], 'stock_transactions_context_date_index');
        });

        Schema::create('acct_stock_transaction_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transaction_id')->constrained('acct_stock_transactions')->cascadeOnDelete();
            $table->unsignedSmallInteger('line_number');
            $table->foreignId('product_id')->constrained('acct_products')->restrictOnDelete();
            $table->foreignId('godown_id')->nullable()->constrained('acct_godowns')->restrictOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->decimal('rate', 15, 2);
            $table->decimal('value', 15, 2);
            $table->timestamps();

            $table->unique(['stock_transaction_id', 'line_number'], 'stock_transaction_lines_number_unique');
        });

        Schema::create('acct_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('acct_branches')->restrictOnDelete();
            $table->foreignId('financial_year_id')->constrained('acct_financial_years')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('acct_products')->restrictOnDelete();
            $table->foreignId('godown_id')->constrained('acct_godowns')->restrictOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->decimal('rate', 15, 2);
            $table->decimal('value', 15, 2);
            $table->string('movement_type', 20);
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->date('movement_date');
            $table->boolean('is_reversal')->default(false);
            $table->timestamps();

            $table->index(['company_id', 'product_id', 'godown_id'], 'stock_movements_balance_index');
            $table->index(['company_id', 'movement_date']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::table('acct_companies', function (Blueprint $table) {
            $table->string('gst_registration_type', 20)->nullable()->after('gstin');
        });

        Schema::table('acct_ledgers', function (Blueprint $table) {
            $table->string('state')->nullable()->after('address');
            $table->string('gst_registration_type', 20)->nullable()->after('gstin');
        });

        Schema::table('acct_products', function (Blueprint $table) {
            $table->foreignId('hsn_sac_id')->nullable()->after('barcode')->constrained('acct_hsn_sacs')->restrictOnDelete();
            $table->foreignId('tax_rate_id')->nullable()->after('hsn_sac_id')->constrained('acct_tax_rates')->restrictOnDelete();
        });

        Schema::table('acct_invoices', function (Blueprint $table) {
            $table->string('supply_type', 10)->nullable()->after('narration');
            $table->string('place_of_supply')->nullable()->after('supply_type');
        });

        Schema::table('acct_invoice_lines', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->foreign('product_id')->references('id')->on('acct_products')->restrictOnDelete();
            }
            $table->foreignId('godown_id')->nullable()->after('product_id')->constrained('acct_godowns')->restrictOnDelete();
            $table->foreignId('tax_rate_id')->nullable()->after('godown_id')->constrained('acct_tax_rates')->restrictOnDelete();
            $table->foreignId('hsn_sac_id')->nullable()->after('tax_rate_id')->constrained('acct_hsn_sacs')->restrictOnDelete();
            $table->decimal('taxable_amount', 15, 2)->default(0)->after('discount');
            $table->decimal('cgst_amount', 15, 2)->default(0)->after('tax_amount');
            $table->decimal('sgst_amount', 15, 2)->default(0)->after('cgst_amount');
            $table->decimal('igst_amount', 15, 2)->default(0)->after('sgst_amount');
            $table->decimal('cess_amount', 15, 2)->default(0)->after('igst_amount');
        });
    }

    public function down(): void
    {
        Schema::table('acct_invoice_lines', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->dropForeign(['product_id']);
            }
            $table->dropConstrainedForeignId('godown_id');
            $table->dropConstrainedForeignId('tax_rate_id');
            $table->dropConstrainedForeignId('hsn_sac_id');
            $table->dropColumn(['taxable_amount', 'cgst_amount', 'sgst_amount', 'igst_amount', 'cess_amount']);
        });

        Schema::table('acct_invoices', function (Blueprint $table) {
            $table->dropColumn(['supply_type', 'place_of_supply']);
        });

        Schema::table('acct_products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tax_rate_id');
            $table->dropConstrainedForeignId('hsn_sac_id');
        });

        Schema::table('acct_ledgers', function (Blueprint $table) {
            $table->dropColumn(['state', 'gst_registration_type']);
        });

        Schema::table('acct_companies', function (Blueprint $table) {
            $table->dropColumn('gst_registration_type');
        });

        Schema::dropIfExists('acct_stock_movements');
        Schema::dropIfExists('acct_stock_transaction_lines');
        Schema::dropIfExists('acct_stock_transactions');
        Schema::dropIfExists('acct_tax_accounts');
        Schema::dropIfExists('acct_hsn_sacs');
        Schema::dropIfExists('acct_tax_rates');
        Schema::dropIfExists('acct_tax_categories');
    }
};
