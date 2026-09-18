<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('target_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('period_type', ['monthly', 'quarterly', 'annual'])->default('monthly');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['period_type', 'starts_on', 'ends_on']);
        });

        Schema::create('party_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('target_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('salesperson_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['target_period_id', 'customer_id']);
            $table->index(['target_period_id', 'salesperson_id']);
        });

        Schema::create('target_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_target_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_period_id')->constrained()->cascadeOnDelete();
            $table->decimal('achieved_amount', 14, 2)->default(0);
            $table->decimal('achievement_percent', 8, 2)->default(0);
            $table->boolean('is_final')->default(false);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique('party_target_id');
        });

        Schema::create('schemes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->enum('status', ['draft', 'active', 'closed', 'cancelled'])->default('draft');
            $table->enum('basis', ['quantity', 'value'])->default('value');
            $table->boolean('net_credit_notes')->default(true);
            $table->text('notes')->nullable();
            $table->string('created_by_name', 100)->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('scheme_slabs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheme_id')->constrained()->cascadeOnDelete();
            $table->decimal('from_value', 14, 2)->default(0);
            $table->decimal('to_value', 14, 2)->nullable();
            $table->decimal('benefit_percent', 8, 2)->default(0);
            $table->decimal('benefit_amount', 14, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('scheme_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheme_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['scheme_id', 'product_id']);
        });

        Schema::create('scheme_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheme_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('qualified_value', 14, 2)->default(0);
            $table->decimal('benefit_amount', 14, 2)->default(0);
            $table->enum('status', ['provisional', 'final', 'cancelled'])->default('provisional');
            $table->json('snapshot')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->index(['scheme_id', 'customer_id', 'status']);
        });

        Schema::create('scheme_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheme_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scheme_achievement_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->enum('status', ['pending', 'settled', 'cancelled'])->default('pending');
            $table->string('settlement_ref')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheme_settlements');
        Schema::dropIfExists('scheme_achievements');
        Schema::dropIfExists('scheme_products');
        Schema::dropIfExists('scheme_slabs');
        Schema::dropIfExists('schemes');
        Schema::dropIfExists('target_achievements');
        Schema::dropIfExists('party_targets');
        Schema::dropIfExists('target_periods');
    }
};
