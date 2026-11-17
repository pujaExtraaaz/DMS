<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Repair product_uoms base rows missing for products.base_uom_id
        if (Schema::hasTable('products') && Schema::hasTable('product_uoms')) {
            $missing = DB::select("
                SELECT p.id AS product_id, p.base_uom_id
                FROM products p
                WHERE p.deleted_at IS NULL
                  AND p.base_uom_id IS NOT NULL
                  AND NOT EXISTS (
                      SELECT 1 FROM product_uoms pu
                      WHERE pu.product_id = p.id
                        AND pu.uom_id = p.base_uom_id
                        AND pu.is_base = 1
                  )
            ");

            foreach ($missing as $row) {
                DB::table('product_uoms')->updateOrInsert(
                    [
                        'product_id' => $row->product_id,
                        'uom_id' => $row->base_uom_id,
                    ],
                    [
                        'conversion_factor' => 1,
                        'is_base' => true,
                        'label' => 'Base',
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        if (Schema::hasTable('purchase_invoice_items')
            && ! Schema::hasColumn('purchase_invoice_items', 'purchase_order_item_id')) {
            Schema::table('purchase_invoice_items', function (Blueprint $table) {
                $table->foreignId('purchase_order_item_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('purchase_order_items')
                    ->nullOnDelete();
            });
        }

        $this->ensureForeignKey('products', 'company_id', 'companies', 'id', 'nullOnDelete');
        $this->ensureForeignKey('products', 'brand_id', 'brands', 'id', 'nullOnDelete');
        $this->ensureForeignKey('products', 'category_id', 'categories', 'id', 'nullOnDelete');
        $this->ensureForeignKey('products', 'sub_category_id', 'sub_categories', 'id', 'nullOnDelete');
        $this->ensureForeignKey('products', 'base_uom_id', 'uoms', 'id', 'restrict');

        $this->ensureForeignKey('purchase_orders', 'supplier_id', 'customers', 'id', 'cascadeOnDelete');
        $this->ensureForeignKey('purchase_orders', 'warehouse_id', 'warehouses', 'id', 'nullOnDelete');
        $this->ensureForeignKey('purchase_order_items', 'purchase_order_id', 'purchase_orders', 'id', 'cascadeOnDelete');
        $this->ensureForeignKey('purchase_order_items', 'product_id', 'products', 'id', 'cascadeOnDelete');
        $this->ensureForeignKey('purchase_order_items', 'uom_id', 'uoms', 'id', 'restrict');

        $this->ensureForeignKey('purchase_invoices', 'supplier_id', 'customers', 'id', 'cascadeOnDelete');
        $this->ensureForeignKey('purchase_invoices', 'warehouse_id', 'warehouses', 'id', 'nullOnDelete');
        $this->ensureForeignKey('purchase_invoices', 'purchase_order_id', 'purchase_orders', 'id', 'nullOnDelete');
        $this->ensureForeignKey('purchase_invoice_items', 'purchase_invoice_id', 'purchase_invoices', 'id', 'cascadeOnDelete');
        $this->ensureForeignKey('purchase_invoice_items', 'product_id', 'products', 'id', 'cascadeOnDelete');
        $this->ensureForeignKey('purchase_invoice_items', 'uom_id', 'uoms', 'id', 'restrict');
    }

    public function down(): void
    {
        // Intentionally empty — restoring integrity constraints should not be rolled back casually.
    }

    protected function ensureForeignKey(
        string $table,
        string $column,
        string $referencedTable,
        string $referencedColumn,
        string $onDelete
    ): void {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column) || ! Schema::hasTable($referencedTable)) {
            return;
        }

        $exists = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        ", [$table, $column]);

        if ($exists) {
            return;
        }

        // Null out orphans before adding FK
        if (in_array($onDelete, ['nullOnDelete', 'restrict'], true)) {
            DB::statement("
                UPDATE {$table} t
                LEFT JOIN {$referencedTable} r ON r.{$referencedColumn} = t.{$column}
                SET t.{$column} = NULL
                WHERE t.{$column} IS NOT NULL AND r.{$referencedColumn} IS NULL
            ");
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $referencedTable, $referencedColumn, $onDelete) {
            $fk = $blueprint->foreign($column)->references($referencedColumn)->on($referencedTable);
            match ($onDelete) {
                'cascadeOnDelete' => $fk->cascadeOnDelete(),
                'nullOnDelete' => $fk->nullOnDelete(),
                default => $fk->restrictOnDelete(),
            };
        });
    }
};
