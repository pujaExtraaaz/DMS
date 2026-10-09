<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acct_currencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 8);
            $table->string('symbol', 8);
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->boolean('is_base')->default(false);
            $table->timestamps();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('acct_voucher_type_masters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('abbreviation', 12);
            $table->string('category', 32);
            $table->string('prefix', 12);
            $table->unsignedInteger('next_number')->default(1);
            $table->unsignedTinyInteger('padding')->default(6);
            $table->timestamps();
            $table->unique(['company_id', 'abbreviation']);
        });

        Schema::create('acct_gst_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('acct_branches')->nullOnDelete();
            $table->string('gstin', 15);
            $table->string('registration_type', 20);
            $table->string('state');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'gstin']);
        });

        Schema::create('acct_merchant_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('acct_branches')->nullOnDelete();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('merchant_code', 40);
            $table->foreignId('settlement_ledger_id')->nullable()->constrained('acct_ledgers')->nullOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('acct_bank_accounts')->nullOnDelete();
            $table->string('notes', 1000)->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'merchant_code']);
        });

        Schema::create('acct_payment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('acct_branches')->restrictOnDelete();
            $table->foreignId('financial_year_id')->constrained('acct_financial_years')->restrictOnDelete();
            $table->foreignId('ledger_id')->constrained('acct_ledgers')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('due_date');
            $table->string('reference', 50)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('narration', 1000)->nullable();
            $table->foreignId('voucher_id')->nullable()->constrained('acct_vouchers')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('acct_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('acct_branches')->nullOnDelete();
            $table->string('name');
            $table->string('employee_code', 20);
            $table->string('designation')->nullable();
            $table->string('pan', 10)->nullable();
            $table->date('joining_date')->nullable();
            $table->foreignId('ledger_id')->nullable()->constrained('acct_ledgers')->nullOnDelete();
            $table->decimal('monthly_earnings', 15, 2)->default(0);
            $table->decimal('monthly_deductions', 15, 2)->default(0);
            $table->timestamps();
            $table->unique(['company_id', 'employee_code']);
        });

        Schema::create('acct_pay_heads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('nature', 20);
            $table->foreignId('ledger_id')->constrained('acct_ledgers')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('acct_payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('acct_branches')->restrictOnDelete();
            $table->foreignId('financial_year_id')->constrained('acct_financial_years')->restrictOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 20)->default('draft');
            $table->foreignId('salary_ledger_id')->constrained('acct_ledgers')->restrictOnDelete();
            $table->foreignId('deduction_ledger_id')->nullable()->constrained('acct_ledgers')->nullOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained('acct_vouchers')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('acct_payroll_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('acct_payroll_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('acct_employees')->restrictOnDelete();
            $table->decimal('earnings', 15, 2);
            $table->decimal('deductions', 15, 2)->default(0);
            $table->decimal('net', 15, 2);
            $table->timestamps();
        });

        Schema::create('acct_sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('acct_branches')->restrictOnDelete();
            $table->foreignId('financial_year_id')->constrained('acct_financial_years')->restrictOnDelete();
            $table->string('number', 32);
            $table->date('order_date');
            $table->foreignId('customer_ledger_id')->constrained('acct_ledgers')->restrictOnDelete();
            $table->string('reference_number')->nullable();
            $table->string('narration', 1000)->nullable();
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2);
            $table->string('status', 20)->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['company_id', 'financial_year_id', 'number'], 'sales_orders_number_unique');
        });

        Schema::create('acct_sales_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('acct_sales_orders')->cascadeOnDelete();
            $table->unsignedInteger('line_number');
            $table->string('item_name');
            $table->foreignId('product_id')->nullable()->constrained('acct_products')->nullOnDelete();
            $table->foreignId('tax_rate_id')->nullable()->constrained('acct_tax_rates')->nullOnDelete();
            $table->foreignId('hsn_sac_id')->nullable()->constrained('acct_hsn_sacs')->nullOnDelete();
            $table->decimal('quantity', 15, 4);
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

        Schema::create('acct_bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained('acct_bank_accounts')->cascadeOnDelete();
            $table->date('statement_date');
            $table->date('value_date')->nullable();
            $table->string('narration', 500);
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('reference', 80)->nullable();
            $table->string('fingerprint', 64);
            $table->foreignId('voucher_entry_id')->nullable()->constrained('acct_voucher_entries')->nullOnDelete();
            $table->timestamps();
            $table->unique(['bank_account_id', 'fingerprint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acct_bank_statement_lines');
        Schema::dropIfExists('acct_sales_order_lines');
        Schema::dropIfExists('acct_sales_orders');
        Schema::dropIfExists('acct_payroll_lines');
        Schema::dropIfExists('acct_payroll_runs');
        Schema::dropIfExists('acct_pay_heads');
        Schema::dropIfExists('acct_employees');
        Schema::dropIfExists('acct_payment_requests');
        Schema::dropIfExists('acct_merchant_profiles');
        Schema::dropIfExists('acct_gst_registrations');
        Schema::dropIfExists('acct_voucher_type_masters');
        Schema::dropIfExists('acct_currencies');
    }
};
