<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('debit_notes')) {
            Schema::create('debit_notes', function (Blueprint $table) {
                $table->id();
                $table->string('debit_note_no', 30)->unique();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('supplier_id')->constrained('customers');
                $table->foreignId('purchase_invoice_id')->nullable()->constrained('purchase_invoices')->nullOnDelete();
                $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
                $table->date('debit_note_date');
                $table->enum('reason', ['return', 'price', 'damage', 'shortage', 'other'])->default('return');
                $table->enum('status', ['draft', 'approved', 'posted', 'cancelled'])->default('draft');
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->decimal('tax_amount', 14, 2)->default(0);
                $table->decimal('grand_total', 14, 2)->default(0);
                $table->boolean('affects_stock')->default(true);
                $table->text('notes')->nullable();
                $table->string('created_by_name', 100)->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('debit_note_items')) {
            Schema::create('debit_note_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('debit_note_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('uom_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('quantity', 12, 4)->default(0);
                $table->decimal('unit_cost', 12, 2)->default(0);
                $table->decimal('tax_amount', 12, 2)->default(0);
                $table->decimal('line_total', 14, 2)->default(0);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('delivery_challans')) {
            Schema::create('delivery_challans', function (Blueprint $table) {
                $table->id();
                $table->string('challan_no', 30)->unique();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('customer_id')->constrained('customers');
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
                $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
                $table->date('challan_date');
                $table->enum('status', ['draft', 'dispatched', 'delivered', 'cancelled'])->default('draft');
                $table->string('vehicle_no', 40)->nullable();
                $table->string('transporter_name')->nullable();
                $table->string('lr_no', 60)->nullable();
                $table->text('shipping_address')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('dispatched_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('delivery_challan_items')) {
            Schema::create('delivery_challan_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('delivery_challan_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('uom_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('quantity', 12, 4)->default(0);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('supplier_payments')) {
            Schema::create('supplier_payments', function (Blueprint $table) {
                $table->id();
                $table->string('payment_no', 30)->unique();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('supplier_id')->constrained('customers');
                $table->foreignId('purchase_invoice_id')->nullable()->constrained('purchase_invoices')->nullOnDelete();
                $table->date('payment_date');
                $table->decimal('amount', 14, 2);
                $table->enum('method', ['cash', 'upi', 'bank', 'cheque', 'other'])->default('bank');
                $table->string('reference_no', 80)->nullable();
                $table->enum('status', ['draft', 'completed', 'cancelled'])->default('completed');
                $table->text('notes')->nullable();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('delivery_challan_items');
        Schema::dropIfExists('delivery_challans');
        Schema::dropIfExists('debit_note_items');
        Schema::dropIfExists('debit_notes');
    }
};
