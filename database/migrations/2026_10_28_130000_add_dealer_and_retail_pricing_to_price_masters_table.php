<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('price_masters')) {
            Schema::table('price_masters', function (Blueprint $table) {
                if (! Schema::hasColumn('price_masters', 'dealer_price')) {
                    $table->decimal('dealer_price', 12, 2)->nullable()->after('entered_rate');
                }
                if (! Schema::hasColumn('price_masters', 'dealer_price_entered')) {
                    $table->decimal('dealer_price_entered', 12, 2)->nullable()->after('dealer_price');
                }
                if (! Schema::hasColumn('price_masters', 'dealer_price_gst_mode')) {
                    $table->string('dealer_price_gst_mode', 20)->default('exclusive')->after('dealer_price_entered');
                }
                if (! Schema::hasColumn('price_masters', 'retail_price')) {
                    $table->decimal('retail_price', 12, 2)->nullable()->after('dealer_price_gst_mode');
                }
                if (! Schema::hasColumn('price_masters', 'retail_price_entered')) {
                    $table->decimal('retail_price_entered', 12, 2)->nullable()->after('retail_price');
                }
                if (! Schema::hasColumn('price_masters', 'retail_price_gst_mode')) {
                    $table->string('retail_price_gst_mode', 20)->default('exclusive')->after('retail_price_entered');
                }
            });

            // Backfill existing records safely
            DB::table('price_masters')->whereNull('dealer_price')->update([
                'dealer_price' => DB::raw('rate'),
                'dealer_price_entered' => DB::raw('COALESCE(entered_rate, rate)'),
                'dealer_price_gst_mode' => DB::raw("CASE WHEN rate_includes_gst = 1 THEN 'inclusive' ELSE 'exclusive' END"),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('price_masters')) {
            Schema::table('price_masters', function (Blueprint $table) {
                $columns = [
                    'retail_price_gst_mode',
                    'retail_price_entered',
                    'retail_price',
                    'dealer_price_gst_mode',
                    'dealer_price_entered',
                    'dealer_price',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('price_masters', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};

