<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Sales Teams (if not already existing)
        if (!Schema::hasTable('sales_teams')) {
            Schema::create('sales_teams', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->foreignId('leader_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name');
                $table->string('code')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['company_id', 'branch_id']);
            });
        }

        // 2. Sales Team Members
        if (!Schema::hasTable('sales_team_members')) {
            Schema::create('sales_team_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sales_team_id')->constrained('sales_teams')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role')->default('member'); // member, co-leader
                $table->timestamps();

                $table->unique(['sales_team_id', 'user_id']);
            });
        }

        // 3. Sales Targets (Header)
        if (!Schema::hasTable('sales_targets')) {
            Schema::create('sales_targets', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->enum('target_type', ['value', 'product', 'sku', 'brand', 'category', 'combined'])->default('value');
                $table->enum('owner_type', ['individual', 'team', 'branch'])->default('individual');
                $table->foreignId('salesperson_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('sales_team_id')->nullable()->constrained('sales_teams')->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->foreignId('financial_year_id')->nullable()->constrained('financial_years')->nullOnDelete();
                
                $table->enum('period_type', ['monthly', 'quarterly', 'half_yearly', 'annual', 'custom'])->default('monthly');
                $table->date('starts_on');
                $table->date('ends_on');
                
                $table->decimal('target_amount', 14, 2)->default(0);
                $table->decimal('target_quantity', 14, 4)->default(0);

                // Quick single-criteria references (optional; detailed lines in sales_target_items)
                $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->string('sku')->nullable();
                $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
                $table->foreignId('sub_category_id')->nullable()->constrained('sub_categories')->nullOnDelete();

                $table->enum('status', ['draft', 'pending_approval', 'active', 'completed', 'cancelled'])->default('active');
                $table->text('notes')->nullable();

                // Approvals, Revisions & Carry-Forward
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                
                $table->unsignedInteger('revision_number')->default(0);
                $table->foreignId('revised_from_id')->nullable()->constrained('sales_targets')->nullOnDelete();
                $table->text('revision_reason')->nullable();
                
                $table->foreignId('carried_forward_from_id')->nullable()->constrained('sales_targets')->nullOnDelete();
                $table->decimal('carried_forward_amount', 14, 2)->default(0);

                $table->timestamps();

                $table->index(['owner_type', 'salesperson_id']);
                $table->index(['owner_type', 'sales_team_id']);
                $table->index(['owner_type', 'branch_id']);
                $table->index(['starts_on', 'ends_on']);
                $table->index(['status', 'target_type']);
            });
        }

        // 4. Sales Target Criteria Items (Line Items for multi-criteria & combined targets)
        if (!Schema::hasTable('sales_target_items')) {
            Schema::create('sales_target_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sales_target_id')->constrained('sales_targets')->cascadeOnDelete();
                $table->string('target_type', 30)->default('product'); // product, sku, brand, category, subcategory
                $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->string('sku')->nullable();
                $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
                $table->foreignId('sub_category_id')->nullable()->constrained('sub_categories')->nullOnDelete();
                
                $table->decimal('target_amount', 14, 2)->default(0);
                $table->decimal('target_quantity', 14, 4)->default(0);
                $table->decimal('achieved_amount', 14, 2)->default(0);
                $table->decimal('achieved_quantity', 14, 4)->default(0);
                $table->string('notes')->nullable();
                $table->timestamps();

                $table->index(['sales_target_id', 'target_type']);
            });
        }

        // 5. Sales Target Cached Achievements
        if (!Schema::hasTable('sales_target_achievements')) {
            Schema::create('sales_target_achievements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sales_target_id')->unique()->constrained('sales_targets')->cascadeOnDelete();
                $table->decimal('achieved_amount', 14, 2)->default(0);
                $table->decimal('achieved_quantity', 14, 4)->default(0);
                $table->decimal('pending_amount', 14, 2)->default(0);
                $table->decimal('pending_quantity', 14, 4)->default(0);
                $table->decimal('achievement_percent', 8, 2)->default(0);
                $table->decimal('quantity_achievement_percent', 8, 2)->default(0);
                $table->boolean('is_achieved')->default(false);
                $table->unsignedInteger('invoices_count')->default(0);
                $table->timestamp('last_calculated_at')->nullable();
                $table->json('breakdown_snapshot')->nullable();
                $table->timestamps();
            });
        }

        // 6. Target Revisions & Audit Log
        if (!Schema::hasTable('sales_target_audits')) {
            Schema::create('sales_target_audits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sales_target_id')->constrained('sales_targets')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 50); // created, updated, approved, revised, cancelled, carry_forward, recalculated
                $table->text('reason')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->timestamps();

                $table->index(['sales_target_id', 'action']);
            });
        }

        // 7. Target Threshold & Pacing Alerts
        if (!Schema::hasTable('sales_target_alerts')) {
            Schema::create('sales_target_alerts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sales_target_id')->constrained('sales_targets')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('alert_type', 40); // threshold_50, threshold_75, threshold_90, threshold_100, underperformance, overdue
                $table->decimal('threshold_percent', 8, 2)->nullable();
                $table->string('title');
                $table->text('message');
                $table->boolean('is_read')->default(false);
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['sales_target_id', 'alert_type']);
                $table->index(['user_id', 'is_read']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_target_alerts');
        Schema::dropIfExists('sales_target_audits');
        Schema::dropIfExists('sales_target_achievements');
        Schema::dropIfExists('sales_target_items');
        Schema::dropIfExists('sales_targets');
        Schema::dropIfExists('sales_team_members');
        Schema::dropIfExists('sales_teams');
    }
};

