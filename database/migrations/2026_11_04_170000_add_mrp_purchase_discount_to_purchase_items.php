<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_order_items')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                if (! Schema::hasColumn('purchase_order_items', 'mrp')) {
                    $table->decimal('mrp', 15, 2)->nullable()->after('unit_cost');
                }
                if (! Schema::hasColumn('purchase_order_items', 'purchase_price_inclusive')) {
                    $table->decimal('purchase_price_inclusive', 15, 4)->nullable()->after('mrp');
                }
                if (! Schema::hasColumn('purchase_order_items', 'purchase_discount_percent')) {
                    $table->decimal('purchase_discount_percent', 8, 2)->nullable()->after('purchase_price_inclusive');
                }
            });
        }

        if (Schema::hasTable('purchase_invoice_items')) {
            Schema::table('purchase_invoice_items', function (Blueprint $table) {
                if (! Schema::hasColumn('purchase_invoice_items', 'purchase_price_inclusive')) {
                    $table->decimal('purchase_price_inclusive', 15, 4)->nullable()->after('unit_cost');
                }
                if (! Schema::hasColumn('purchase_invoice_items', 'purchase_discount_percent')) {
                    $table->decimal('purchase_discount_percent', 8, 2)->nullable()->after('purchase_price_inclusive');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchase_order_items')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                foreach (['mrp', 'purchase_price_inclusive', 'purchase_discount_percent'] as $col) {
                    if (Schema::hasColumn('purchase_order_items', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('purchase_invoice_items')) {
            Schema::table('purchase_invoice_items', function (Blueprint $table) {
                foreach (['purchase_price_inclusive', 'purchase_discount_percent'] as $col) {
                    if (Schema::hasColumn('purchase_invoice_items', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
