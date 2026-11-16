<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipt_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('receipt_note_no', 60);
            $table->date('receipt_date');
            $table->foreignId('supplier_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->string('supplier_challan_no', 100)->nullable();
            $table->date('supplier_challan_date')->nullable();
            $table->string('received_by', 100)->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->text('remarks')->nullable();
            $table->string('status', 40)->default('draft');
            $table->string('attachment_path', 255)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['supplier_id', 'receipt_date']);
            $table->index('receipt_note_no');
        });

        Schema::create('receipt_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_note_id')->constrained('receipt_notes')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained('purchase_order_items')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('uom_id')->constrained('uoms')->cascadeOnDelete();
            $table->decimal('ordered_qty', 12, 4)->default(0);
            $table->decimal('challan_qty', 12, 4)->default(0);
            $table->decimal('received_qty', 12, 4)->default(0);
            $table->decimal('invoiced_qty', 12, 4)->default(0);
            $table->decimal('returned_qty', 12, 4)->default(0);
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->string('batch_no', 80)->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('remarks', 255)->nullable();
            $table->timestamps();

            $table->index(['receipt_note_id', 'product_id']);
        });

        Schema::create('receipt_note_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('receipt_note_id')->constrained('receipt_notes')->cascadeOnDelete();
            $table->string('return_no', 60)->unique();
            $table->date('return_date');
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->string('reference_no', 100)->nullable();
            $table->string('reason', 255)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'return_date']);
        });

        Schema::create('receipt_note_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_note_return_id')->constrained('receipt_note_returns')->cascadeOnDelete();
            $table->foreignId('receipt_note_item_id')->constrained('receipt_note_items')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('uom_id')->constrained('uoms')->cascadeOnDelete();
            $table->decimal('quantity', 12, 4)->default(0);
            $table->string('remarks', 255)->nullable();
            $table->timestamps();
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->foreignId('receipt_note_id')->nullable()->after('purchase_order_id')->constrained('receipt_notes')->nullOnDelete();
        });

        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->foreignId('receipt_note_item_id')->nullable()->after('product_id')->constrained('receipt_note_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('receipt_note_item_id');
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('receipt_note_id');
        });

        Schema::dropIfExists('receipt_note_return_items');
        Schema::dropIfExists('receipt_note_returns');
        Schema::dropIfExists('receipt_note_items');
        Schema::dropIfExists('receipt_notes');
    }
};
