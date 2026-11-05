<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. invoices (Sales Tax Invoices)
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (! Schema::hasColumn('invoices', 'price_type')) {
                    $table->string('price_type', 20)->default('exclusive')->after('credit_days');
                }
                if (! Schema::hasColumn('invoices', 'tax_type')) {
                    $table->string('tax_type', 20)->nullable()->after('price_type');
                }
                if (! Schema::hasColumn('invoices', 'place_of_supply_state')) {
                    $table->string('place_of_supply_state', 100)->nullable()->after('delivery_state');
                }
                if (! Schema::hasColumn('invoices', 'taxable_amount')) {
                    $table->decimal('taxable_amount', 14, 2)->default(0)->after('item_discount_total');
                }
                if (! Schema::hasColumn('invoices', 'cgst_amount')) {
                    $table->decimal('cgst_amount', 14, 2)->default(0)->after('tax_amount');
                }
                if (! Schema::hasColumn('invoices', 'sgst_amount')) {
                    $table->decimal('sgst_amount', 14, 2)->default(0)->after('cgst_amount');
                }
                if (! Schema::hasColumn('invoices', 'igst_amount')) {
                    $table->decimal('igst_amount', 14, 2)->default(0)->after('sgst_amount');
                }
                if (! Schema::hasColumn('invoices', 'round_off')) {
                    $table->decimal('round_off', 10, 2)->default(0)->after('tax_amount');
                }
            });
        }

        // 2. invoice_items (Sales Invoice Lines)
        if (Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                if (! Schema::hasColumn('invoice_items', 'price_type')) {
                    $table->string('price_type', 20)->default('exclusive')->after('unit_price');
                }
                if (! Schema::hasColumn('invoice_items', 'entered_unit_price')) {
                    $table->decimal('entered_unit_price', 12, 2)->nullable()->after('price_type');
                }
                if (! Schema::hasColumn('invoice_items', 'taxable_amount')) {
                    $table->decimal('taxable_amount', 12, 2)->default(0)->after('discount_value');
                }
                if (! Schema::hasColumn('invoice_items', 'tax_rate')) {
                    $table->decimal('tax_rate', 6, 2)->default(0)->after('taxable_amount');
                }
                if (! Schema::hasColumn('invoice_items', 'cgst_rate')) {
                    $table->decimal('cgst_rate', 6, 2)->default(0)->after('tax_rate');
                }
                if (! Schema::hasColumn('invoice_items', 'sgst_rate')) {
                    $table->decimal('sgst_rate', 6, 2)->default(0)->after('cgst_rate');
                }
                if (! Schema::hasColumn('invoice_items', 'igst_rate')) {
                    $table->decimal('igst_rate', 6, 2)->default(0)->after('sgst_rate');
                }
                if (! Schema::hasColumn('invoice_items', 'cgst_amount')) {
                    $table->decimal('cgst_amount', 12, 2)->default(0)->after('tax_amount');
                }
                if (! Schema::hasColumn('invoice_items', 'sgst_amount')) {
                    $table->decimal('sgst_amount', 12, 2)->default(0)->after('cgst_amount');
                }
                if (! Schema::hasColumn('invoice_items', 'igst_amount')) {
                    $table->decimal('igst_amount', 12, 2)->default(0)->after('sgst_amount');
                }
            });
        }

        // 3. purchase_invoices
        if (Schema::hasTable('purchase_invoices')) {
            Schema::table('purchase_invoices', function (Blueprint $table) {
                if (! Schema::hasColumn('purchase_invoices', 'price_type')) {
                    $table->string('price_type', 20)->default('exclusive')->after('status');
                }
                if (! Schema::hasColumn('purchase_invoices', 'tax_type')) {
                    $table->string('tax_type', 20)->nullable()->after('price_type');
                }
                if (! Schema::hasColumn('purchase_invoices', 'round_off')) {
                    $table->decimal('round_off', 10, 2)->default(0)->after('other_charges');
                }
                if (! Schema::hasColumn('purchase_invoices', 'taxable_amount')) {
                    $table->decimal('taxable_amount', 14, 2)->default(0)->after('subtotal');
                }
                if (! Schema::hasColumn('purchase_invoices', 'cgst_amount')) {
                    $table->decimal('cgst_amount', 14, 2)->default(0)->after('tax_amount');
                }
                if (! Schema::hasColumn('purchase_invoices', 'sgst_amount')) {
                    $table->decimal('sgst_amount', 14, 2)->default(0)->after('cgst_amount');
                }
                if (! Schema::hasColumn('purchase_invoices', 'igst_amount')) {
                    $table->decimal('igst_amount', 14, 2)->default(0)->after('sgst_amount');
                }
            });
        }

        // 4. purchase_invoice_items
        if (Schema::hasTable('purchase_invoice_items')) {
            Schema::table('purchase_invoice_items', function (Blueprint $table) {
                if (! Schema::hasColumn('purchase_invoice_items', 'price_type')) {
                    $table->string('price_type', 20)->default('exclusive')->after('unit_cost');
                }
                if (! Schema::hasColumn('purchase_invoice_items', 'entered_unit_cost')) {
                    $table->decimal('entered_unit_cost', 14, 4)->nullable()->after('price_type');
                }
                if (! Schema::hasColumn('purchase_invoice_items', 'igst_percent')) {
                    $table->decimal('igst_percent', 8, 2)->default(0)->after('sgst_percent');
                }
                if (! Schema::hasColumn('purchase_invoice_items', 'igst_amount')) {
                    $table->decimal('igst_amount', 15, 2)->default(0)->after('sgst_amount');
                }
                if (! Schema::hasColumn('purchase_invoice_items', 'taxable_amount')) {
                    $table->decimal('taxable_amount', 15, 2)->default(0)->after('igst_amount');
                }
            });
        }

        // 5. orders (Sales Orders)
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (! Schema::hasColumn('orders', 'price_type')) {
                    $table->string('price_type', 20)->default('exclusive')->after('status');
                }
                if (! Schema::hasColumn('orders', 'round_off')) {
                    $table->decimal('round_off', 10, 2)->default(0)->after('tax_amount');
                }
            });
        }

        // 6. purchase_orders
        if (Schema::hasTable('purchase_orders')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                if (! Schema::hasColumn('purchase_orders', 'price_type')) {
                    $table->string('price_type', 20)->default('exclusive')->after('status');
                }
                if (! Schema::hasColumn('purchase_orders', 'round_off')) {
                    $table->decimal('round_off', 10, 2)->default(0)->after('tax_amount');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                $cols = ['price_type', 'tax_type', 'place_of_supply_state', 'taxable_amount', 'cgst_amount', 'sgst_amount', 'igst_amount', 'round_off'];
                foreach ($cols as $c) {
                    if (Schema::hasColumn('invoices', $c)) {
                        $table->dropColumn($c);
                    }
                }
            });
        }

        if (Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $cols = ['price_type', 'entered_unit_price', 'taxable_amount', 'tax_rate', 'cgst_rate', 'sgst_rate', 'igst_rate', 'cgst_amount', 'sgst_amount', 'igst_amount'];
                foreach ($cols as $c) {
                    if (Schema::hasColumn('invoice_items', $c)) {
                        $table->dropColumn($c);
                    }
                }
            });
        }

        if (Schema::hasTable('purchase_invoices')) {
            Schema::table('purchase_invoices', function (Blueprint $table) {
                $cols = ['price_type', 'tax_type', 'round_off', 'taxable_amount', 'cgst_amount', 'sgst_amount', 'igst_amount'];
                foreach ($cols as $c) {
                    if (Schema::hasColumn('purchase_invoices', $c)) {
                        $table->dropColumn($c);
                    }
                }
            });
        }

        if (Schema::hasTable('purchase_invoice_items')) {
            Schema::table('purchase_invoice_items', function (Blueprint $table) {
                $cols = ['price_type', 'entered_unit_cost', 'igst_percent', 'igst_amount', 'taxable_amount'];
                foreach ($cols as $c) {
                    if (Schema::hasColumn('purchase_invoice_items', $c)) {
                        $table->dropColumn($c);
                    }
                }
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $cols = ['price_type', 'round_off'];
                foreach ($cols as $c) {
                    if (Schema::hasColumn('orders', $c)) {
                        $table->dropColumn($c);
                    }
                }
            });
        }

        if (Schema::hasTable('purchase_orders')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $cols = ['price_type', 'round_off'];
                foreach ($cols as $c) {
                    if (Schema::hasColumn('purchase_orders', $c)) {
                        $table->dropColumn($c);
                    }
                }
            });
        }
    }
};
