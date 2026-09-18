<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_uoms', function (Blueprint $table) {
            foreach ([
                'label' => fn () => $table->string('label', 60)->nullable()->after('is_base'),
                'selling_price' => fn () => $table->decimal('selling_price', 12, 2)->nullable()->after('label'),
                'trade_price' => fn () => $table->decimal('trade_price', 12, 2)->nullable()->after('selling_price'),
                'purchase_price' => fn () => $table->decimal('purchase_price', 12, 2)->nullable()->after('trade_price'),
                'mrp' => fn () => $table->decimal('mrp', 12, 2)->nullable()->after('purchase_price'),
                'is_default_sales' => fn () => $table->boolean('is_default_sales')->default(false)->after('mrp'),
                'is_active' => fn () => $table->boolean('is_active')->default(true)->after('is_default_sales'),
            ] as $column => $adder) {
                if (! Schema::hasColumn('product_uoms', $column)) {
                    $adder();
                }
            }
        });

        // Backfill: mirror base uom prices into the base ProductUom row for existing products.
        \DB::statement('
            UPDATE product_uoms pu
            JOIN products p ON p.id = pu.product_id
            SET pu.selling_price = COALESCE(pu.selling_price, p.selling_price),
                pu.trade_price   = COALESCE(pu.trade_price, p.trade_price),
                pu.purchase_price = COALESCE(pu.purchase_price, p.purchase_price),
                pu.mrp = COALESCE(pu.mrp, p.calculation_mrp),
                pu.label = COALESCE(pu.label, "Base"),
                pu.is_default_sales = CASE WHEN pu.is_base = 1 THEN 1 ELSE pu.is_default_sales END
            WHERE pu.is_base = 1
        ');

        // Make sure product_price_histories has a reason + author column we can enrich (idempotent).
        Schema::table('product_price_histories', function (Blueprint $table) {
            if (! Schema::hasColumn('product_price_histories', 'change_reason')) {
                $table->string('change_reason', 100)->nullable()->after('effective_to');
            }
            if (! Schema::hasColumn('product_price_histories', 'source')) {
                $table->string('source', 30)->default('manual')->after('change_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_uoms', function (Blueprint $table) {
            foreach (['label', 'selling_price', 'trade_price', 'purchase_price', 'mrp', 'is_default_sales', 'is_active'] as $column) {
                if (Schema::hasColumn('product_uoms', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('product_price_histories', function (Blueprint $table) {
            foreach (['change_reason', 'source'] as $column) {
                if (Schema::hasColumn('product_price_histories', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
