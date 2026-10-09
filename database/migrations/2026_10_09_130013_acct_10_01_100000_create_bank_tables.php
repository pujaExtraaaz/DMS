<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acct_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->foreignId('ledger_id')->unique()->constrained('acct_ledgers')->cascadeOnDelete();
            $table->string('bank_name');
            $table->string('account_number', 30);
            $table->string('ifsc', 11);
            $table->timestamps();

            $table->unique(['company_id', 'account_number'], 'bank_accounts_number_unique');
            $table->index(['company_id', 'ifsc']);
        });

        Schema::create('acct_bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->foreignId('ledger_id')->constrained('acct_ledgers')->restrictOnDelete();
            $table->foreignId('voucher_entry_id')->unique()->constrained('acct_voucher_entries')->cascadeOnDelete();
            $table->string('reference', 50)->nullable();
            $table->date('transaction_date');
            $table->decimal('book_amount', 15, 2);
            $table->decimal('bank_amount', 15, 2);
            $table->string('status', 20);
            $table->date('reconciled_on')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'ledger_id', 'status'], 'bank_reconciliations_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acct_bank_reconciliations');
        Schema::dropIfExists('acct_bank_accounts');
    }
};
