<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('purchase_invoice_items')) {
            return;
        }

        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_invoice_items', 'is_general')) {
                $table->boolean('is_general')->default(false)->after('product_id');
            }
            if (! Schema::hasColumn('purchase_invoice_items', 'description')) {
                $table->string('description', 255)->nullable()->after('is_general');
            }
        });

        $this->dropProductForeignKey();

        DB::statement('ALTER TABLE purchase_invoice_items MODIFY product_id BIGINT UNSIGNED NULL');

        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('purchase_invoice_items')) {
            return;
        }

        $this->dropProductForeignKey();

        // Cannot safely re-tighten NOT NULL if general rows exist; leave nullable on rollback of FK only.
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_invoice_items', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('purchase_invoice_items', 'is_general')) {
                $table->dropColumn('is_general');
            }
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });
    }

    protected function dropProductForeignKey(): void
    {
        $fk = DB::selectOne("
            SELECT CONSTRAINT_NAME AS name
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'purchase_invoice_items'
              AND COLUMN_NAME = 'product_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        ");

        if ($fk && ! empty($fk->name)) {
            DB::statement('ALTER TABLE purchase_invoice_items DROP FOREIGN KEY `'.$fk->name.'`');
        }
    }
};
