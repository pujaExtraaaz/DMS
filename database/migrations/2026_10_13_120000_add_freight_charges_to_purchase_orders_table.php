<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('purchase_orders')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                if (! Schema::hasColumn('purchase_orders', 'is_freight_applicable')) {
                    $table->boolean('is_freight_applicable')->default(false)->after('status');
                }
                if (! Schema::hasColumn('purchase_orders', 'freight_amount')) {
                    $table->decimal('freight_amount', 14, 2)->default(0)->after('is_freight_applicable');
                }
                if (! Schema::hasColumn('purchase_orders', 'freight_tax_rate')) {
                    $table->decimal('freight_tax_rate', 8, 2)->default(0)->after('freight_amount');
                }
                if (! Schema::hasColumn('purchase_orders', 'freight_tax_amount')) {
                    $table->decimal('freight_tax_amount', 14, 2)->default(0)->after('freight_tax_rate');
                }
                if (! Schema::hasColumn('purchase_orders', 'freight_total')) {
                    $table->decimal('freight_total', 14, 2)->default(0)->after('freight_tax_amount');
                }
                if (! Schema::hasColumn('purchase_orders', 'freight_notes')) {
                    $table->string('freight_notes', 500)->nullable()->after('freight_total');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('purchase_orders')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $columns = [
                    'freight_notes',
                    'freight_total',
                    'freight_tax_amount',
                    'freight_tax_rate',
                    'freight_amount',
                    'is_freight_applicable',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('purchase_orders', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};

