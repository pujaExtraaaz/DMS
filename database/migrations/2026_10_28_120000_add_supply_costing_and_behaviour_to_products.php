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
            if (! Schema::hasColumn('products', 'supply_type')) {
                $table->string('supply_type', 30)->default('goods');
            }
            if (! Schema::hasColumn('products', 'purchase_price_tax_basis')) {
                $table->string('purchase_price_tax_basis', 20)->default('exclusive');
            }
            if (! Schema::hasColumn('products', 'costing_method')) {
                $table->string('costing_method', 40)->nullable();
            }
            if (! Schema::hasColumn('products', 'market_valuation_method')) {
                $table->string('market_valuation_method', 40)->default('avg_price');
            }
            if (! Schema::hasColumn('products', 'provide_behaviour')) {
                $table->boolean('provide_behaviour')->default(false);
            }
            if (! Schema::hasColumn('products', 'ignore_physical_count_diff')) {
                $table->boolean('ignore_physical_count_diff')->default(false);
            }
            if (! Schema::hasColumn('products', 'ignore_negative_balances')) {
                $table->boolean('ignore_negative_balances')->default(false);
            }
            if (! Schema::hasColumn('products', 'treat_sales_as_new_manufacture')) {
                $table->boolean('treat_sales_as_new_manufacture')->default(false);
            }
            if (! Schema::hasColumn('products', 'treat_purchases_as_consumed')) {
                $table->boolean('treat_purchases_as_consumed')->default(false);
            }
            if (! Schema::hasColumn('products', 'treat_rejections_inward_as_scrap')) {
                $table->boolean('treat_rejections_inward_as_scrap')->default(false);
            }
            if (! Schema::hasColumn('products', 'last_sale_price')) {
                $table->decimal('last_sale_price', 12, 2)->nullable();
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM(
                'purchase','sale','adjustment','return','delivery_short',
                'transfer_in','transfer_out','opening','inward','reclassification',
                'purchase_invoice','consumption','manufacture','scrap'
            ) NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM(
                'purchase','sale','adjustment','return','delivery_short',
                'transfer_in','transfer_out','opening','inward','reclassification'
            ) NOT NULL");
        }

        Schema::table('products', function (Blueprint $table) {
            foreach ([
                'supply_type',
                'purchase_price_tax_basis',
                'costing_method',
                'market_valuation_method',
                'provide_behaviour',
                'ignore_physical_count_diff',
                'ignore_negative_balances',
                'treat_sales_as_new_manufacture',
                'treat_purchases_as_consumed',
                'treat_rejections_inward_as_scrap',
                'last_sale_price',
            ] as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
