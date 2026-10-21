<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_orders', 'freight_mode')) {
                $table->string('freight_mode', 10)->default('amount')->after('is_freight_applicable');
            }
            if (! Schema::hasColumn('purchase_orders', 'freight_percent')) {
                $table->decimal('freight_percent', 8, 4)->nullable()->after('freight_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_orders', 'freight_percent')) {
                $table->dropColumn('freight_percent');
            }
            if (Schema::hasColumn('purchase_orders', 'freight_mode')) {
                $table->dropColumn('freight_mode');
            }
        });
    }
};
