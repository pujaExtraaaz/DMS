<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 30)->unique();
            $table->date('comparison_date');
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('indent_no', 60)->nullable();
            $table->string('customer_project')->nullable();
            $table->date('required_delivery_date')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status', 30)->default('draft');
            $table->text('executive_remarks')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_comparison_vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_comparison_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('customers')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->text('billing_address')->nullable();
            $table->text('dispatch_address')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('mobile', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('gstin', 20)->nullable();
            $table->string('quotation_number', 60)->nullable();
            $table->date('quotation_date')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('credit_period', 40)->nullable();
            $table->string('advance_payment', 80)->nullable();
            $table->string('credit_limit_note')->nullable();
            $table->string('replacement_policy')->nullable();
            $table->string('return_policy')->nullable();
            $table->string('installation_support')->nullable();
            $table->string('transporter')->nullable();
            $table->string('freight_paid_by', 20)->default('vendor');
            $table->string('delivery_location')->nullable();
            $table->boolean('partial_delivery')->default(false);
            $table->timestamps();
        });

        Schema::create('purchase_comparison_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_comparison_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->string('category_name')->nullable();
            $table->string('subcategory_name')->nullable();
            $table->string('brand_name')->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('uoms')->nullOnDelete();
            $table->string('item_name');
            $table->string('model_sku')->nullable();
            $table->text('description')->nullable();
            $table->decimal('required_quantity', 15, 4)->default(1);
            $table->foreignId('selected_vendor_id')->nullable()->constrained('purchase_comparison_vendors')->nullOnDelete();
            $table->text('selection_reason')->nullable();
            $table->decimal('final_rate', 15, 4)->nullable();
            $table->decimal('final_freight', 15, 2)->nullable();
            $table->decimal('final_amount', 15, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_comparison_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_comparison_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_comparison_vendor_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_quoted')->default(false);
            $table->decimal('quoted_quantity', 15, 4)->nullable();
            $table->decimal('basic_rate', 15, 4)->nullable();
            $table->string('discount_type', 10)->default('percent');
            $table->decimal('discount_value', 15, 4)->default(0);
            $table->decimal('gst_rate', 8, 2)->default(0);
            $table->string('freight_type', 10)->default('free');
            $table->string('freight_mode', 10)->default('amount');
            $table->decimal('freight_value', 15, 2)->default(0);
            $table->decimal('packing_charges', 15, 2)->default(0);
            $table->decimal('insurance_charges', 15, 2)->default(0);
            $table->decimal('other_charges', 15, 2)->default(0);
            $table->string('warranty_period', 60)->nullable();
            $table->string('warranty_type', 20)->nullable();
            $table->string('stock_availability', 20)->nullable();
            $table->string('delivery_time', 60)->nullable();
            $table->date('delivery_date')->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('price_validity', 60)->nullable();
            $table->string('country_of_origin', 60)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['purchase_comparison_item_id', 'purchase_comparison_vendor_id'], 'comparison_quote_item_vendor_unique');
        });

        Schema::create('purchase_comparison_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_comparison_vendor_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 120)->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_comparison_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_comparison_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 40);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_comparison_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_comparison_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_comparison_vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_comparison_orders');
        Schema::dropIfExists('purchase_comparison_events');
        Schema::dropIfExists('purchase_comparison_files');
        Schema::dropIfExists('purchase_comparison_quotes');
        Schema::dropIfExists('purchase_comparison_items');
        Schema::dropIfExists('purchase_comparison_vendors');
        Schema::dropIfExists('purchase_comparisons');
    }
};
