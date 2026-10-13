<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('acct_inventory_impairments')) {
            Schema::create('acct_inventory_impairments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained('acct_branches')->cascadeOnDelete();
                $table->foreignId('financial_year_id')->constrained('acct_financial_years')->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('acct_products')->nullOnDelete();
                $table->date('entry_date');
                $table->decimal('amount', 18, 2);
                $table->foreignId('expense_ledger_id')->constrained('acct_ledgers')->restrictOnDelete();
                $table->foreignId('provision_ledger_id')->constrained('acct_ledgers')->restrictOnDelete();
                $table->foreignId('voucher_id')->nullable()->constrained('acct_vouchers')->nullOnDelete();
                $table->string('narration', 500)->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['company_id', 'entry_date'], 'acct_inv_imp_co_date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('acct_inventory_impairments');
    }
};
