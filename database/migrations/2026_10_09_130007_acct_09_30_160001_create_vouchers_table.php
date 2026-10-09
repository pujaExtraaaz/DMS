<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acct_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('acct_branches')->restrictOnDelete();
            $table->foreignId('financial_year_id')->constrained('acct_financial_years')->restrictOnDelete();
            $table->string('voucher_type', 20);
            $table->string('voucher_number', 30);
            $table->date('voucher_date');
            $table->string('reference_number', 50)->nullable();
            $table->string('narration', 1000)->nullable();
            $table->string('status', 20);
            $table->decimal('total_debit', 15, 2);
            $table->decimal('total_credit', 15, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['company_id', 'branch_id', 'financial_year_id', 'voucher_type', 'voucher_number'],
                'vouchers_number_unique'
            );
            $table->index(['company_id', 'branch_id', 'financial_year_id', 'voucher_date'], 'vouchers_context_date_index');
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'voucher_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acct_vouchers');
    }
};
