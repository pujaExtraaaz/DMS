<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_comparison_vendors', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_comparison_vendors', 'payment_mode')) {
                $table->string('payment_mode', 30)->nullable()->after('advance_payment');
            }
            if (! Schema::hasColumn('purchase_comparison_vendors', 'credit_days')) {
                $table->unsignedSmallInteger('credit_days')->nullable()->after('payment_mode');
            }
            if (! Schema::hasColumn('purchase_comparison_vendors', 'payment_due_date')) {
                $table->date('payment_due_date')->nullable()->after('credit_days');
            }
            if (! Schema::hasColumn('purchase_comparison_vendors', 'pdc_details')) {
                $table->string('pdc_details')->nullable()->after('payment_due_date');
            }
            if (! Schema::hasColumn('purchase_comparison_vendors', 'cdc_details')) {
                $table->string('cdc_details')->nullable()->after('pdc_details');
            }
            if (! Schema::hasColumn('purchase_comparison_vendors', 'cash_discount_percent')) {
                $table->decimal('cash_discount_percent', 8, 2)->default(0)->after('cdc_details');
            }
            if (! Schema::hasColumn('purchase_comparison_vendors', 'cash_discount_days')) {
                $table->unsignedSmallInteger('cash_discount_days')->nullable()->after('cash_discount_percent');
            }
            if (! Schema::hasColumn('purchase_comparison_vendors', 'bank_interest_percent')) {
                $table->decimal('bank_interest_percent', 8, 2)->default(0)->after('cash_discount_days');
            }
            if (! Schema::hasColumn('purchase_comparison_vendors', 'bank_interest_days')) {
                $table->unsignedSmallInteger('bank_interest_days')->nullable()->after('bank_interest_percent');
            }
            if (! Schema::hasColumn('purchase_comparison_vendors', 'financing_notes')) {
                $table->string('financing_notes')->nullable()->after('bank_interest_days');
            }
        });

        Schema::table('purchase_comparison_quotes', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_comparison_quotes', 'tax_basis')) {
                $table->string('tax_basis', 20)->default('exclusive')->after('gst_rate');
            }
            if (! Schema::hasColumn('purchase_comparison_quotes', 'cash_discount_percent')) {
                $table->decimal('cash_discount_percent', 8, 2)->default(0)->after('discount_value');
            }
            if (! Schema::hasColumn('purchase_comparison_quotes', 'financing_cost')) {
                $table->decimal('financing_cost', 15, 2)->default(0)->after('other_charges');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_comparison_vendors', function (Blueprint $table) {
            foreach ([
                'payment_mode', 'credit_days', 'payment_due_date', 'pdc_details', 'cdc_details',
                'cash_discount_percent', 'cash_discount_days', 'bank_interest_percent',
                'bank_interest_days', 'financing_notes',
            ] as $column) {
                if (Schema::hasColumn('purchase_comparison_vendors', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('purchase_comparison_quotes', function (Blueprint $table) {
            foreach (['tax_basis', 'cash_discount_percent', 'financing_cost'] as $column) {
                if (Schema::hasColumn('purchase_comparison_quotes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
