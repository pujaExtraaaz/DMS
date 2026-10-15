<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('branches', 'deleted_at')) {
            DB::statement('ALTER TABLE `branches` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL');
        }

        if (Schema::hasTable('warehouses') && ! Schema::hasColumn('warehouses', 'deleted_at')) {
            DB::statement('ALTER TABLE `warehouses` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('branches', 'deleted_at')) {
            DB::statement('ALTER TABLE `branches` DROP COLUMN `deleted_at`');
        }

        if (Schema::hasTable('warehouses') && Schema::hasColumn('warehouses', 'deleted_at')) {
            DB::statement('ALTER TABLE `warehouses` DROP COLUMN `deleted_at`');
        }
    }
};
