<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acct_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('acct_branches')->restrictOnDelete();
            $table->foreignId('financial_year_id')->constrained('acct_financial_years')->restrictOnDelete();
            $table->string('kind', 20);
            $table->string('invoice_number', 30);
            $table->date('invoice_date');
            $table->foreignId('party_ledger_id')->constrained('acct_ledgers')->restrictOnDelete();
            $table->foreignId('account_ledger_id')->constrained('acct_ledgers')->restrictOnDelete();
            $table->string('reference_number', 50)->nullable();
            $table->string('narration', 1000)->nullable();
            $table->string('status', 20);
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount_total', 15, 2);
            $table->decimal('tax_total', 15, 2);
            $table->decimal('grand_total', 15, 2);
            $table->foreignId('voucher_id')->nullable()->unique()->constrained('acct_vouchers')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['company_id', 'branch_id', 'financial_year_id', 'kind', 'invoice_number'],
                'invoices_number_unique'
            );
            $table->index(['company_id', 'branch_id', 'financial_year_id', 'invoice_date'], 'invoices_context_date_index');
            $table->index(['company_id', 'kind', 'status']);
        });

        Schema::create('acct_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('acct_invoices')->cascadeOnDelete();
            $table->unsignedSmallInteger('line_number');
            $table->string('item_name');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->decimal('quantity', 15, 3);
            $table->decimal('rate', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            $table->timestamps();

            $table->unique(['invoice_id', 'line_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acct_invoice_lines');
        Schema::dropIfExists('acct_invoices');
    }
};
