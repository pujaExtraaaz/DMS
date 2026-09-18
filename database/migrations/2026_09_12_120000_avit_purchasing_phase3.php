<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_invoices', 'terms_and_conditions')) {
                $table->text('terms_and_conditions')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('purchase_invoices', 'freight_allocation_method')) {
                $table->string('freight_allocation_method', 20)->nullable()->after('terms_and_conditions');
            }
        });

        Schema::table('purchase_inward_items', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_inward_items', 'batch_selling_price')) {
                $table->decimal('batch_selling_price', 12, 2)->nullable()->after('expiry_date');
            }
            if (! Schema::hasColumn('purchase_inward_items', 'batch_mrp')) {
                $table->decimal('batch_mrp', 12, 2)->nullable()->after('batch_selling_price');
            }
            if (! Schema::hasColumn('purchase_inward_items', 'rejection_reason')) {
                $table->string('rejection_reason', 255)->nullable()->after('batch_mrp');
            }
        });

        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_invoice_items', 'batch_no')) {
                $table->string('batch_no', 60)->nullable()->after('quantity');
            }
            if (! Schema::hasColumn('purchase_invoice_items', 'batch_selling_price')) {
                $table->decimal('batch_selling_price', 12, 2)->nullable()->after('batch_no');
            }
            if (! Schema::hasColumn('purchase_invoice_items', 'batch_mrp')) {
                $table->decimal('batch_mrp', 12, 2)->nullable()->after('batch_selling_price');
            }
            if (! Schema::hasColumn('purchase_invoice_items', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('batch_mrp');
            }
        });

        Schema::table('product_batches', function (Blueprint $table) {
            if (! Schema::hasColumn('product_batches', 'selling_price')) {
                $table->decimal('selling_price', 12, 2)->nullable()->after('unit_cost');
            }
            if (! Schema::hasColumn('product_batches', 'mrp')) {
                $table->decimal('mrp', 12, 2)->nullable()->after('selling_price');
            }
        });

        Schema::table('freight_bills', function (Blueprint $table) {
            if (! Schema::hasColumn('freight_bills', 'allocation_basis')) {
                $table->string('allocation_basis', 20)->default('value')->after('total_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            foreach (['terms_and_conditions', 'freight_allocation_method'] as $c) {
                if (Schema::hasColumn('purchase_invoices', $c)) $table->dropColumn($c);
            }
        });
        Schema::table('purchase_inward_items', function (Blueprint $table) {
            foreach (['batch_selling_price', 'batch_mrp', 'rejection_reason'] as $c) {
                if (Schema::hasColumn('purchase_inward_items', $c)) $table->dropColumn($c);
            }
        });
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            foreach (['batch_no', 'batch_selling_price', 'batch_mrp', 'expiry_date'] as $c) {
                if (Schema::hasColumn('purchase_invoice_items', $c)) $table->dropColumn($c);
            }
        });
        Schema::table('product_batches', function (Blueprint $table) {
            foreach (['selling_price', 'mrp'] as $c) {
                if (Schema::hasColumn('product_batches', $c)) $table->dropColumn($c);
            }
        });
        Schema::table('freight_bills', function (Blueprint $table) {
            if (Schema::hasColumn('freight_bills', 'allocation_basis')) $table->dropColumn('allocation_basis');
        });
    }
};
