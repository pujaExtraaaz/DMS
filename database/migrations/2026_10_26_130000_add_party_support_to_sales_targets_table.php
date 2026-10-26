<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales_targets')) {
            Schema::table('sales_targets', function (Blueprint $table) {
                if (!Schema::hasColumn('sales_targets', 'customer_id')) {
                    $table->foreignId('customer_id')->nullable()->after('branch_id')->constrained('customers')->nullOnDelete();
                    $table->index(['owner_type', 'customer_id']);
                }
            });

            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE `sales_targets` MODIFY COLUMN `owner_type` VARCHAR(30) NOT NULL DEFAULT 'individual'");
                DB::statement("ALTER TABLE `sales_targets` MODIFY COLUMN `target_type` VARCHAR(30) NOT NULL DEFAULT 'value'");
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales_targets')) {
            Schema::table('sales_targets', function (Blueprint $table) {
                if (Schema::hasColumn('sales_targets', 'customer_id')) {
                    $table->dropForeign(['customer_id']);
                    $table->dropColumn('customer_id');
                }
            });
        }
    }
};

