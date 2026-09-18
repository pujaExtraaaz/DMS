<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_cost_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uom_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity_remaining', 14, 4)->default(0);
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->decimal('landed_unit_cost', 14, 4)->nullable();
            $table->date('received_on')->nullable();
            $table->nullableMorphs('source');
            $table->string('batch_no', 80)->nullable();
            $table->timestamps();

            $table->index(['product_id', 'warehouse_id', 'received_on'], 'stock_cost_layers_lookup_idx');
        });

        Schema::create('stock_cost_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_cost_layer_id')->constrained('stock_cost_layers')->cascadeOnDelete();
            $table->foreignId('stock_movement_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 14, 4);
            $table->decimal('unit_cost', 14, 4);
            $table->nullableMorphs('reference');
            $table->timestamps();
        });

        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'due_date_basis')) {
                $table->enum('due_date_basis', ['invoice_date', 'inward_date'])->default('invoice_date')->after('bank_ifsc');
            }
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_invoices', 'due_date_basis')) {
                $table->string('due_date_basis', 30)->nullable()->after('due_date');
            }
            if (! Schema::hasColumn('purchase_invoices', 'due_date_source_date')) {
                $table->date('due_date_source_date')->nullable()->after('due_date_basis');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'due_date_source_date')) {
                $table->date('due_date_source_date')->nullable()->after('due_date_basis');
            }
        });

        Schema::table('product_serials', function (Blueprint $table) {
            if (! Schema::hasColumn('product_serials', 'reserved_for_type')) {
                $table->nullableMorphs('reserved_for');
            }
            if (! Schema::hasColumn('product_serials', 'reservation_note')) {
                $table->string('reservation_note', 150)->nullable()->after('notes');
            }
            if (! Schema::hasColumn('product_serials', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('sold_at');
            }
            if (! Schema::hasColumn('product_serials', 'returned_at')) {
                $table->timestamp('returned_at')->nullable()->after('delivered_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_serials', function (Blueprint $table) {
            if (Schema::hasColumn('product_serials', 'reserved_for_type')) {
                $table->dropMorphs('reserved_for');
            }
            foreach (['delivered_at', 'returned_at', 'reservation_note'] as $col) {
                if (Schema::hasColumn('product_serials', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'due_date_source_date')) {
                $table->dropColumn('due_date_source_date');
            }
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            foreach (['due_date_basis', 'due_date_source_date'] as $col) {
                if (Schema::hasColumn('purchase_invoices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'due_date_basis')) {
                $table->dropColumn('due_date_basis');
            }
        });

        Schema::dropIfExists('stock_cost_consumptions');
        Schema::dropIfExists('stock_cost_layers');
    }
};
