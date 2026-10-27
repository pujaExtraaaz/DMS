<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('purchase_order_items')) {
            return;
        }

        if (! Schema::hasColumn('purchase_order_items', 'serial_no')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                $table->string('serial_no', 100)->nullable()->after('batch_no');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('purchase_order_items')) {
            return;
        }

        if (Schema::hasColumn('purchase_order_items', 'serial_no')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                $table->dropColumn('serial_no');
            });
        }
    }
};
