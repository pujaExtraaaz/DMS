<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_invoices', 'is_freight_applicable')) {
                $table->boolean('is_freight_applicable')->default(false)->after('freight_charge');
            }
            if (! Schema::hasColumn('purchase_invoices', 'freight_mode')) {
                $table->string('freight_mode', 10)->default('amount')->after('is_freight_applicable');
            }
            if (! Schema::hasColumn('purchase_invoices', 'freight_percent')) {
                $table->decimal('freight_percent', 8, 4)->nullable()->after('freight_mode');
            }
            if (! Schema::hasColumn('purchase_invoices', 'freight_amount')) {
                $table->decimal('freight_amount', 14, 2)->default(0)->after('freight_percent');
            }
            if (! Schema::hasColumn('purchase_invoices', 'freight_tax_rate')) {
                $table->decimal('freight_tax_rate', 8, 2)->default(0)->after('freight_amount');
            }
            if (! Schema::hasColumn('purchase_invoices', 'freight_tax_amount')) {
                $table->decimal('freight_tax_amount', 14, 2)->default(0)->after('freight_tax_rate');
            }
            if (! Schema::hasColumn('purchase_invoices', 'freight_total')) {
                $table->decimal('freight_total', 14, 2)->default(0)->after('freight_tax_amount');
            }
            if (! Schema::hasColumn('purchase_invoices', 'freight_notes')) {
                $table->string('freight_notes', 1000)->nullable()->after('freight_total');
            }
            if (! Schema::hasColumn('purchase_invoices', 'receipt_note_nos')) {
                $table->string('receipt_note_nos', 255)->nullable()->after('freight_notes');
            }
            if (! Schema::hasColumn('purchase_invoices', 'receipt_doc_no')) {
                $table->string('receipt_doc_no', 100)->nullable()->after('receipt_note_nos');
            }
            if (! Schema::hasColumn('purchase_invoices', 'dispatched_through')) {
                $table->string('dispatched_through', 150)->nullable()->after('receipt_doc_no');
            }
            if (! Schema::hasColumn('purchase_invoices', 'carrier_name')) {
                $table->string('carrier_name', 150)->nullable()->after('dispatched_through');
            }
            if (! Schema::hasColumn('purchase_invoices', 'lr_number')) {
                $table->string('lr_number', 100)->nullable()->after('carrier_name');
            }
            if (! Schema::hasColumn('purchase_invoices', 'lr_date')) {
                $table->date('lr_date')->nullable()->after('lr_number');
            }
            if (! Schema::hasColumn('purchase_invoices', 'vehicle_number')) {
                $table->string('vehicle_number', 50)->nullable()->after('lr_date');
            }
            if (! Schema::hasColumn('purchase_invoices', 'order_nos')) {
                $table->string('order_nos', 255)->nullable()->after('vehicle_number');
            }
            if (! Schema::hasColumn('purchase_invoices', 'payment_terms')) {
                $table->string('payment_terms', 255)->nullable()->after('order_nos');
            }
            if (! Schema::hasColumn('purchase_invoices', 'other_references')) {
                $table->string('other_references', 255)->nullable()->after('payment_terms');
            }
            if (! Schema::hasColumn('purchase_invoices', 'terms_of_delivery')) {
                $table->string('terms_of_delivery', 255)->nullable()->after('other_references');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            foreach ([
                'terms_of_delivery', 'other_references', 'payment_terms', 'order_nos',
                'vehicle_number', 'lr_date', 'lr_number', 'carrier_name', 'dispatched_through',
                'receipt_doc_no', 'receipt_note_nos', 'freight_notes', 'freight_total',
                'freight_tax_amount', 'freight_tax_rate', 'freight_amount', 'freight_percent',
                'freight_mode', 'is_freight_applicable',
            ] as $column) {
                if (Schema::hasColumn('purchase_invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
