<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acct_voucher_type_masters', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_voucher_type_masters', 'numbering_method')) {
                $table->string('numbering_method', 24)->default('automatic')->after('category');
            }
            if (! Schema::hasColumn('acct_voucher_type_masters', 'suffix')) {
                $table->string('suffix', 12)->nullable()->after('prefix');
            }
            if (! Schema::hasColumn('acct_voucher_type_masters', 'restart')) {
                $table->string('restart', 16)->default('yearly')->after('padding');
            }
        });

        Schema::table('acct_voucher_sequences', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_voucher_sequences', 'suffix')) {
                $table->string('suffix', 12)->nullable()->after('prefix');
            }
            if (! Schema::hasColumn('acct_voucher_sequences', 'period_key')) {
                $table->string('period_key', 16)->default('')->after('voucher_type');
            }
        });

        Schema::table('acct_voucher_sequences', function (Blueprint $table) {
            $table->dropUnique('voucher_sequences_scope_unique');
            $table->unique(
                ['company_id', 'branch_id', 'financial_year_id', 'voucher_type', 'period_key'],
                'voucher_sequences_scope_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('acct_voucher_sequences', function (Blueprint $table) {
            $table->dropUnique('voucher_sequences_scope_unique');
            $table->unique(
                ['company_id', 'branch_id', 'financial_year_id', 'voucher_type'],
                'voucher_sequences_scope_unique'
            );
            if (Schema::hasColumn('acct_voucher_sequences', 'period_key')) {
                $table->dropColumn('period_key');
            }
            if (Schema::hasColumn('acct_voucher_sequences', 'suffix')) {
                $table->dropColumn('suffix');
            }
        });

        Schema::table('acct_voucher_type_masters', function (Blueprint $table) {
            foreach (['restart', 'suffix', 'numbering_method'] as $column) {
                if (Schema::hasColumn('acct_voucher_type_masters', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};