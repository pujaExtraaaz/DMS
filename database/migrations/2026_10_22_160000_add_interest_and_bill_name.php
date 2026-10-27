<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acct_ledgers', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_ledgers', 'calculate_interest')) {
                $table->boolean('calculate_interest')->default(false)->after('cost_centres_applicable');
            }
            if (! Schema::hasColumn('acct_ledgers', 'interest_rate')) {
                $table->decimal('interest_rate', 8, 4)->nullable()->after('calculate_interest');
            }
            if (! Schema::hasColumn('acct_ledgers', 'interest_style')) {
                $table->string('interest_style', 20)->default('365_day_year')->after('interest_rate');
            }
        });

        Schema::table('acct_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_invoices', 'bill_name')) {
                $table->string('bill_name', 40)->nullable()->after('invoice_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('acct_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('acct_invoices', 'bill_name')) {
                $table->dropColumn('bill_name');
            }
        });

        Schema::table('acct_ledgers', function (Blueprint $table) {
            foreach (['interest_style', 'interest_rate', 'calculate_interest'] as $column) {
                if (Schema::hasColumn('acct_ledgers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};