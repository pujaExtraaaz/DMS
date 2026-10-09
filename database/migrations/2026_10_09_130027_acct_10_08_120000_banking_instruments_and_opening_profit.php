<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acct_financial_years', function (Blueprint $table) {
            $table->decimal('opening_profit', 15, 2)->default(0)->after('is_active');
        });

        Schema::create('acct_bank_instruments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('acct_branches')->restrictOnDelete();
            $table->foreignId('financial_year_id')->constrained('acct_financial_years')->restrictOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('acct_bank_accounts')->nullOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained('acct_vouchers')->nullOnDelete();
            $table->foreignId('party_ledger_id')->nullable()->constrained('acct_ledgers')->nullOnDelete();
            $table->string('number', 30);
            $table->date('instrument_date');
            $table->decimal('amount', 15, 2);
            $table->string('favouring', 160);
            $table->string('status', 20)->default('open');
            $table->string('narration', 500)->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'number'], 'bank_instruments_number_unique');
            $table->index(['company_id', 'financial_year_id', 'instrument_date'], 'bank_instruments_date_index');
            $table->index(['company_id', 'status']);
        });

        Schema::create('acct_deposit_slips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('acct_branches')->restrictOnDelete();
            $table->foreignId('financial_year_id')->constrained('acct_financial_years')->restrictOnDelete();
            $table->foreignId('bank_account_id')->constrained('acct_bank_accounts')->restrictOnDelete();
            $table->string('slip_number', 30);
            $table->date('slip_date');
            $table->decimal('amount', 15, 2);
            $table->string('narration', 500)->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'slip_number'], 'deposit_slips_number_unique');
        });

        Schema::create('acct_deposit_slip_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deposit_slip_id')->constrained('acct_deposit_slips')->cascadeOnDelete();
            $table->foreignId('bank_instrument_id')->constrained('acct_bank_instruments')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->unique(['deposit_slip_id', 'bank_instrument_id'], 'deposit_slip_lines_unique');
        });

        Schema::create('acct_payment_advices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('acct_branches')->restrictOnDelete();
            $table->foreignId('financial_year_id')->constrained('acct_financial_years')->restrictOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('acct_bank_accounts')->nullOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained('acct_vouchers')->nullOnDelete();
            $table->foreignId('party_ledger_id')->nullable()->constrained('acct_ledgers')->nullOnDelete();
            $table->string('advice_number', 30);
            $table->date('advice_date');
            $table->decimal('amount', 15, 2);
            $table->string('narration', 500)->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'advice_number'], 'payment_advices_number_unique');
        });

        Schema::create('acct_gateway_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('acct_branches')->restrictOnDelete();
            $table->foreignId('financial_year_id')->constrained('acct_financial_years')->restrictOnDelete();
            $table->foreignId('merchant_profile_id')->nullable()->constrained('acct_merchant_profiles')->nullOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('acct_bank_accounts')->nullOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained('acct_vouchers')->nullOnDelete();
            $table->string('reference', 40);
            $table->date('settlement_date');
            $table->decimal('gross_amount', 15, 2);
            $table->decimal('charges', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2);
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->unique(['company_id', 'reference'], 'gateway_settlements_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acct_gateway_settlements');
        Schema::dropIfExists('acct_payment_advices');
        Schema::dropIfExists('acct_deposit_slip_lines');
        Schema::dropIfExists('acct_deposit_slips');
        Schema::dropIfExists('acct_bank_instruments');
        Schema::table('acct_financial_years', function (Blueprint $table) {
            $table->dropColumn('opening_profit');
        });
    }
};
