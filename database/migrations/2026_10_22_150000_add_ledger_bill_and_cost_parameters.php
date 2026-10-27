<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acct_ledgers', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_ledgers', 'maintain_bill_wise')) {
                $table->boolean('maintain_bill_wise')->default(true)->after('credit_days');
            }
            if (! Schema::hasColumn('acct_ledgers', 'cost_centres_applicable')) {
                $table->boolean('cost_centres_applicable')->default(false)->after('maintain_bill_wise');
            }
        });

        Schema::table('acct_bill_allocations', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_bill_allocations', 'reference_type')) {
                $table->string('reference_type', 20)->default('agst_ref')->after('ledger_id');
            }
            if (! Schema::hasColumn('acct_bill_allocations', 'reference_name')) {
                $table->string('reference_name', 40)->nullable()->after('reference_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('acct_bill_allocations', function (Blueprint $table) {
            foreach (['reference_name', 'reference_type'] as $column) {
                if (Schema::hasColumn('acct_bill_allocations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('acct_ledgers', function (Blueprint $table) {
            foreach (['cost_centres_applicable', 'maintain_bill_wise'] as $column) {
                if (Schema::hasColumn('acct_ledgers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};