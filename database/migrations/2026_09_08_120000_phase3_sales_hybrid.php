<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_no', 30)->unique();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('salesperson_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('quotation_date');
            $table->date('valid_until')->nullable();
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'converted', 'cancelled'])->default('draft');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->decimal('estimated_profit', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('created_by_name', 100)->nullable();
            $table->string('updated_by_name', 100)->nullable();
            $table->foreignId('converted_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('uom_id')->constrained();
            $table->decimal('quantity', 12, 4);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('line_total', 14, 2);
            $table->decimal('estimated_landed_cost', 12, 2)->default(0);
            $table->decimal('estimated_profit', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('region_brand_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_allowed')->default(true);
            $table->decimal('max_discount_percent', 5, 2)->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unique(['area_id', 'brand_id']);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'due_date')) {
                $table->date('due_date')->nullable()->after('order_date');
            }
            if (! Schema::hasColumn('orders', 'fulfilment_mode')) {
                $table->enum('fulfilment_mode', ['warehouse', 'van'])->default('van')->after('due_date');
            }
            if (! Schema::hasColumn('orders', 'back_order')) {
                $table->boolean('back_order')->default(false)->after('fulfilment_mode');
            }
            if (! Schema::hasColumn('orders', 'credit_check_status')) {
                $table->enum('credit_check_status', ['pending', 'passed', 'blocked', 'overridden'])->default('pending')->after('back_order');
            }
            if (! Schema::hasColumn('orders', 'blocked_reason')) {
                $table->text('blocked_reason')->nullable()->after('credit_check_status');
            }
            if (! Schema::hasColumn('orders', 'warehouse_id')) {
                $table->foreignId('warehouse_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('orders', 'quotation_id')) {
                $table->foreignId('quotation_id')->nullable()->after('warehouse_id')->constrained()->nullOnDelete();
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'reserved_qty')) {
                $table->decimal('reserved_qty', 12, 4)->default(0)->after('quantity');
            }
            if (! Schema::hasColumn('order_items', 'delivered_qty')) {
                $table->decimal('delivered_qty', 12, 4)->default(0)->after('reserved_qty');
            }
            if (! Schema::hasColumn('order_items', 'back_order_qty')) {
                $table->decimal('back_order_qty', 12, 4)->default(0)->after('delivered_qty');
            }
        });

        if (! Schema::hasTable('stock_reservations')) {
            Schema::create('stock_reservations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained();
                $table->foreignId('uom_id')->constrained();
                $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('quantity', 12, 4);
                $table->enum('status', ['active', 'released', 'fulfilled', 'partial'])->default('active');
                $table->date('due_date')->nullable()->index();
                $table->timestamp('reserved_at')->nullable();
                $table->timestamp('released_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['product_id', 'uom_id', 'status']);
            });
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'due_date')) {
                $table->date('due_date')->nullable()->after('invoice_date');
            }
            if (! Schema::hasColumn('invoices', 'due_date_basis')) {
                $table->enum('due_date_basis', ['invoice_date', 'inward_date'])->default('invoice_date')->after('due_date');
            }
            if (! Schema::hasColumn('invoices', 'payment_terms')) {
                $table->string('payment_terms')->nullable()->after('due_date_basis');
            }
            if (! Schema::hasColumn('invoices', 'credit_days')) {
                $table->unsignedInteger('credit_days')->default(0)->after('payment_terms');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            foreach (['due_date', 'due_date_basis', 'payment_terms', 'credit_days'] as $column) {
                if (Schema::hasColumn('invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (Schema::hasTable('stock_reservations')) {
            Schema::dropIfExists('stock_reservations');
        }

        Schema::table('order_items', function (Blueprint $table) {
            foreach (['reserved_qty', 'delivered_qty', 'back_order_qty'] as $column) {
                if (Schema::hasColumn('order_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'quotation_id')) {
                $table->dropConstrainedForeignId('quotation_id');
            }
            if (Schema::hasColumn('orders', 'warehouse_id')) {
                $table->dropConstrainedForeignId('warehouse_id');
            }
            foreach (['due_date', 'fulfilment_mode', 'back_order', 'credit_check_status', 'blocked_reason'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('region_brand_policies');
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
    }
};
