<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acct_companies', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_companies', 'use_cost_centres')) {
                $table->boolean('use_cost_centres')->default(true)->after('accounting_basis');
            }
            if (! Schema::hasColumn('acct_companies', 'use_bill_wise')) {
                $table->boolean('use_bill_wise')->default(true)->after('use_cost_centres');
            }
            if (! Schema::hasColumn('acct_companies', 'lock_closed_financial_year')) {
                $table->boolean('lock_closed_financial_year')->default(true)->after('use_bill_wise');
            }
            if (! Schema::hasColumn('acct_companies', 'default_credit_days')) {
                $table->unsignedSmallInteger('default_credit_days')->nullable()->after('lock_closed_financial_year');
            }
            if (! Schema::hasColumn('acct_companies', 'round_off_ledger_id')) {
                $table->foreignId('round_off_ledger_id')->nullable()->after('commission_payable_ledger_id')
                    ->constrained('acct_ledgers')->nullOnDelete();
            }
        });

        Schema::table('acct_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_invoices', 'round_off')) {
                $table->decimal('round_off', 15, 2)->default(0)->after('grand_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('acct_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('acct_invoices', 'round_off')) {
                $table->dropColumn('round_off');
            }
        });

        Schema::table('acct_companies', function (Blueprint $table) {
            if (Schema::hasColumn('acct_companies', 'round_off_ledger_id')) {
                $table->dropConstrainedForeignId('round_off_ledger_id');
            }
            $columns = array_values(array_filter(
                ['use_cost_centres', 'use_bill_wise', 'lock_closed_financial_year', 'default_credit_days'],
                fn (string $column) => Schema::hasColumn('acct_companies', $column),
            ));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
