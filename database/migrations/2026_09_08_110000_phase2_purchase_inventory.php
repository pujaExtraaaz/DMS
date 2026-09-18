<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('customers')->cascadeOnDelete();
            $table->string('po_no', 40)->unique();
            $table->date('po_date');
            $table->date('expected_date')->nullable();
            $table->enum('status', [
                'draft',
                'pending_approval',
                'approved',
                'partially_received',
                'closed',
                'cancelled',
            ])->default('draft');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uom_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 14, 4);
            $table->decimal('received_qty', 14, 4)->default(0);
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->decimal('tax_percent', 8, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->decimal('weight', 14, 4)->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_inwards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('inward_no', 40)->unique();
            $table->date('inward_date');
            $table->string('supplier_challan_no', 60)->nullable();
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('posted');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('purchase_inward_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_inward_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uom_id')->constrained()->cascadeOnDelete();
            $table->decimal('ordered_qty', 14, 4)->default(0);
            $table->decimal('received_qty', 14, 4);
            $table->decimal('accepted_qty', 14, 4);
            $table->decimal('rejected_qty', 14, 4)->default(0);
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->string('batch_no', 60)->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_inward_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_no', 40)->unique();
            $table->string('supplier_invoice_no', 60)->nullable();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('posted');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->text('rate_override_reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('purchase_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uom_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 14, 4);
            $table->decimal('unit_cost', 14, 4);
            $table->decimal('tax_percent', 8, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->decimal('other_vendor_rate', 14, 4)->nullable();
            $table->timestamps();
        });

        Schema::create('vendor_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('unit_cost', 14, 4);
            $table->date('effective_from');
            $table->nullableMorphs('reference');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['product_id', 'supplier_id', 'effective_from'], 'vendor_price_hist_lookup_idx');
        });

        Schema::create('freight_bills', function (Blueprint $table) {
            $table->id();
            $table->string('freight_no', 40)->unique();
            $table->date('bill_date');
            $table->string('transporter_name')->nullable();
            $table->string('vehicle_no', 40)->nullable();
            $table->string('lr_no', 60)->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->enum('status', ['draft', 'posted', 'allocated', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('freight_bill_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('freight_bill_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('allocatable');
            $table->decimal('amount', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('landed_costs', function (Blueprint $table) {
            $table->id();
            $table->string('landed_no', 40)->unique();
            $table->date('landed_date');
            $table->foreignId('purchase_invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('freight_bill_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('allocation_method', ['qty', 'value', 'weight', 'volume', 'equal', 'manual'])->default('value');
            $table->decimal('total_additional_cost', 14, 2)->default(0);
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('landed_cost_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landed_cost_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 14, 4)->default(0);
            $table->decimal('base_value', 14, 2)->default(0);
            $table->decimal('weight', 14, 4)->default(0);
            $table->decimal('volume', 14, 4)->default(0);
            $table->decimal('allocated_cost', 14, 2)->default(0);
            $table->decimal('landed_unit_cost', 14, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('supplier_payables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('customers')->cascadeOnDelete();
            $table->enum('type', ['invoice', 'payment', 'adjustment', 'debit_note', 'credit_note'])->default('invoice');
            $table->nullableMorphs('reference');
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->decimal('balance', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['supplier_id', 'id']);
        });

        Schema::create('product_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('serial_number')->unique();
            $table->enum('status', ['in_stock', 'reserved', 'sold', 'returned', 'scrapped', 'in_transit'])->default('in_stock');
            $table->nullableMorphs('source');
            $table->foreignId('purchase_inward_item_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('sold_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_no', 60);
            $table->date('mfg_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity', 14, 4)->default(0);
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->nullableMorphs('source');
            $table->timestamps();
            $table->unique(['product_id', 'warehouse_id', 'batch_no'], 'product_batches_lookup_unique');
        });

        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 14, 4);
            $table->enum('status', ['active', 'released', 'consumed', 'cancelled'])->default('active');
            $table->nullableMorphs('reference');
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_no', 40)->unique();
            $table->date('transfer_date');
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->enum('status', ['draft', 'in_transit', 'received', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uom_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 14, 4);
            $table->timestamps();
        });

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_no', 40)->unique();
            $table->date('adjustment_date');
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('reason', ['opening', 'damage', 'shrinkage', 'found', 'recount', 'other'])->default('other');
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_adjustment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uom_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 14, 4);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_valuation_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_year_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('method', ['fifo', 'lifo', 'weighted_avg'])->default('fifo');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'financial_year_id'], 'stock_valuation_company_fy_unique');
        });

        Schema::create('demo_stock_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('serial_number')->nullable();
            $table->string('batch_no', 60)->nullable();
            $table->date('notice_date');
            $table->date('expected_return_date')->nullable();
            $table->enum('status', ['out', 'returned', 'converted', 'cancelled'])->default('out');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('internal_delivery_challans', function (Blueprint $table) {
            $table->id();
            $table->string('challan_no', 40)->unique();
            $table->date('challan_date');
            $table->foreignId('from_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('to_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('stock_transfer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purpose')->nullable();
            $table->enum('status', ['draft', 'dispatched', 'received', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('stock_levels', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        // FK on product_id uses the existing unique index; add a dedicated index before dropping it.
        Schema::table('stock_levels', function (Blueprint $table) {
            $table->index('product_id', 'stock_levels_product_id_index');
        });

        Schema::table('stock_levels', function (Blueprint $table) {
            $table->dropUnique('stock_levels_product_id_uom_id_unique');
            $table->unique(['warehouse_id', 'product_id', 'uom_id'], 'stock_levels_warehouse_product_uom_unique');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM(
            'purchase','sale','adjustment','return','delivery_short',
            'transfer_in','transfer_out','opening','inward'
        ) NOT NULL");

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('supplier_party_id')->nullable()->after('supplier_name')->constrained('customers')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->after('supplier_party_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropConstrainedForeignId('supplier_party_id');
        });

        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM(
            'purchase','sale','adjustment','return','delivery_short'
        ) NOT NULL");

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
        });

        Schema::table('stock_levels', function (Blueprint $table) {
            $table->dropUnique('stock_levels_warehouse_product_uom_unique');
        });

        Schema::table('stock_levels', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
            $table->unique(['product_id', 'uom_id'], 'stock_levels_product_id_uom_id_unique');
            $table->dropIndex('stock_levels_product_id_index');
        });

        Schema::dropIfExists('internal_delivery_challans');
        Schema::dropIfExists('demo_stock_notices');
        Schema::dropIfExists('stock_valuation_settings');
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_reservations');
        Schema::dropIfExists('product_batches');
        Schema::dropIfExists('product_serials');
        Schema::dropIfExists('supplier_payables');
        Schema::dropIfExists('landed_cost_items');
        Schema::dropIfExists('landed_costs');
        Schema::dropIfExists('freight_bill_allocations');
        Schema::dropIfExists('freight_bills');
        Schema::dropIfExists('vendor_price_histories');
        Schema::dropIfExists('purchase_invoice_items');
        Schema::dropIfExists('purchase_invoices');
        Schema::dropIfExists('purchase_inward_items');
        Schema::dropIfExists('purchase_inwards');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
