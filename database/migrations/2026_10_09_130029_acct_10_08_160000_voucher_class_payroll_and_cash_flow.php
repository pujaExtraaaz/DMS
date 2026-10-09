<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acct_voucher_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
            $table->string('voucher_type', 30);
            $table->string('name');
            $table->foreignId('default_ledger_id')->nullable()->constrained('acct_ledgers')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'voucher_type', 'name']);
        });

        Schema::table('acct_ledgers', function (Blueprint $table) {
            $table->string('cash_flow_class', 20)->nullable();
        });

        Schema::table('acct_vouchers', function (Blueprint $table) {
            $table->foreignId('voucher_class_id')->nullable()->constrained('acct_voucher_classes')->nullOnDelete();
            $table->string('nature_of_payment')->nullable();
            $table->boolean('is_memo')->default(false);
            $table->date('reverses_on')->nullable();
            $table->foreignId('reversed_voucher_id')->nullable()->constrained('acct_vouchers')->nullOnDelete();
        });

        Schema::table('acct_payroll_lines', function (Blueprint $table) {
            $table->decimal('employer_pf_amount', 15, 2)->default(0);
            $table->decimal('employer_esi_amount', 15, 2)->default(0);
        });

        Schema::table('acct_payroll_runs', function (Blueprint $table) {
            $table->foreignId('statutory_voucher_id')->nullable()->constrained('acct_vouchers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('acct_payroll_runs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('statutory_voucher_id');
        });

        Schema::table('acct_payroll_lines', function (Blueprint $table) {
            $table->dropColumn(['employer_pf_amount', 'employer_esi_amount']);
        });

        Schema::table('acct_vouchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reversed_voucher_id');
            $table->dropConstrainedForeignId('voucher_class_id');
            $table->dropColumn(['nature_of_payment', 'is_memo', 'reverses_on']);
        });

        Schema::table('acct_ledgers', function (Blueprint $table) {
            $table->dropColumn('cash_flow_class');
        });

        Schema::dropIfExists('acct_voucher_classes');
    }
};
