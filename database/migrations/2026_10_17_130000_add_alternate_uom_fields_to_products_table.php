<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'alternate_uom_id')) {
                $table->foreignId('alternate_uom_id')->nullable()->after('base_uom_id')->constrained('uoms')->nullOnDelete();
            }
            if (! Schema::hasColumn('products', 'conversion_factor')) {
                $table->decimal('conversion_factor', 12, 4)->nullable()->default(1)->after('alternate_uom_id');
            }
            if (! Schema::hasColumn('products', 'alt_purchase_price')) {
                $table->decimal('alt_purchase_price', 12, 2)->nullable()->after('calculation_mrp');
            }
            if (! Schema::hasColumn('products', 'alt_trade_price')) {
                $table->decimal('alt_trade_price', 12, 2)->nullable()->after('alt_purchase_price');
            }
            if (! Schema::hasColumn('products', 'alt_selling_price')) {
                $table->decimal('alt_selling_price', 12, 2)->nullable()->after('alt_trade_price');
            }
            if (! Schema::hasColumn('products', 'alt_calculation_mrp')) {
                $table->decimal('alt_calculation_mrp', 12, 2)->nullable()->after('alt_selling_price');
            }
        });

        // Sync initial values from product_uoms where an alternate unit already exists
        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                UPDATE products p
                JOIN product_uoms pu ON pu.product_id = p.id AND pu.is_base = 0
                SET p.alternate_uom_id = COALESCE(p.alternate_uom_id, pu.uom_id),
                    p.conversion_factor = COALESCE(p.conversion_factor, pu.conversion_factor),
                    p.alt_purchase_price = COALESCE(p.alt_purchase_price, pu.purchase_price),
                    p.alt_trade_price = COALESCE(p.alt_trade_price, pu.trade_price),
                    p.alt_selling_price = COALESCE(p.alt_selling_price, pu.selling_price),
                    p.alt_calculation_mrp = COALESCE(p.alt_calculation_mrp, pu.mrp)
                WHERE pu.id = (
                    SELECT MIN(id) FROM product_uoms WHERE product_id = p.id AND is_base = 0
                )
            ');
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'alternate_uom_id')) {
                $table->dropForeign(['alternate_uom_id']);
                $table->dropColumn('alternate_uom_id');
            }
            foreach (['conversion_factor', 'alt_purchase_price', 'alt_trade_price', 'alt_selling_price', 'alt_calculation_mrp'] as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

