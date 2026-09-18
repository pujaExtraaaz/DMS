<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->enum('accounting_treatment', ['trade_discount', 'deal_expense', 'landed_cost'])->default('deal_expense');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('site_name')->nullable();
            $table->enum('status', ['draft', 'active', 'closed', 'cancelled'])->default('draft');
            $table->decimal('sale_amount', 14, 2)->default(0);
            $table->decimal('landed_cost', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('deal_cost_total', 14, 2)->default(0);
            $table->decimal('net_margin', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('created_by_name', 100)->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
        });

        Schema::create('deal_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_type_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('party_name')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected', 'posted'])->default('draft');
            $table->string('created_by_name', 100)->nullable();
            $table->timestamps();

            $table->index(['deal_id', 'status']);
        });

        Schema::create('expense_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_expense_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approver_name', 100)->nullable();
            $table->enum('decision', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('reason')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_approvals');
        Schema::dropIfExists('deal_expenses');
        Schema::dropIfExists('deals');
        Schema::dropIfExists('expense_types');
    }
};
