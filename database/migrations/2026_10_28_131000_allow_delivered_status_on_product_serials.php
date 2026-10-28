<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE product_serials MODIFY COLUMN status ENUM('in_stock', 'reserved', 'sold', 'returned', 'scrapped', 'in_transit', 'delivered') NOT NULL DEFAULT 'in_stock'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE product_serials MODIFY COLUMN status ENUM('in_stock', 'reserved', 'sold', 'returned', 'scrapped', 'in_transit') NOT NULL DEFAULT 'in_stock'");
    }
};
