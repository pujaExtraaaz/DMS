<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acct_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('acct_branches')->restrictOnDelete();
            $table->foreignId('financial_year_id')->nullable()->constrained('acct_financial_years')->nullOnDelete();
            $table->foreignId('ledger_id')->constrained('acct_ledgers')->restrictOnDelete();
            $table->foreignId('voucher_entry_id')->nullable()->unique()->constrained('acct_voucher_entries')->cascadeOnDelete();
            $table->string('bill_number', 40);
            $table->date('bill_date');
            $table->date('due_date');
            $table->decimal('original_amount', 15, 2);
            $table->string('side', 6);
            $table->boolean('is_opening')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'ledger_id', 'bill_number'], 'bills_number_unique');
            $table->index(['company_id', 'ledger_id', 'bill_date'], 'bills_party_date_index');
        });

        Schema::create('acct_bill_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->foreignId('bill_id')->nullable()->constrained('acct_bills')->restrictOnDelete();
            $table->foreignId('voucher_id')->constrained('acct_vouchers')->cascadeOnDelete();
            $table->foreignId('ledger_id')->constrained('acct_ledgers')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->index(['voucher_id', 'ledger_id']);
            $table->index(['bill_id', 'ledger_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acct_bill_allocations');
        Schema::dropIfExists('acct_bills');
    }
};
