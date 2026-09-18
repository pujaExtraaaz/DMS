<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->string('legal_name')->nullable();
            $table->string('gstin', 20)->nullable();
            $table->string('pan', 20)->nullable();
            $table->string('cin', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('state', 100)->nullable();
            $table->string('pincode', 12)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_no', 50)->nullable();
            $table->string('bank_ifsc', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('company_group_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unique(['business_group_id', 'company_id']);
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 30);
            $table->text('address')->nullable();
            $table->string('state', 100)->nullable();
            $table->string('pincode', 12)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('gstin', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unique(['company_id', 'code']);
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 30);
            $table->text('address')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unique(['branch_id', 'code']);
            $table->timestamps();
        });

        Schema::create('financial_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_closed')->default(false);
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('sub_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 30);
            $table->boolean('is_active')->default(true);
            $table->unique(['category_id', 'code']);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->after('brand_id')->constrained()->nullOnDelete();
            $table->foreignId('sub_category_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
            $table->text('specification')->nullable()->after('description');
            $table->decimal('trade_price', 12, 2)->default(0)->after('selling_price');
            $table->decimal('purchase_price', 12, 2)->default(0)->after('trade_price');
            $table->decimal('calculation_mrp', 12, 2)->default(0)->after('purchase_price');
            $table->json('mrp_calculation_rules')->nullable()->after('calculation_mrp');
            $table->decimal('discount_percent', 5, 2)->default(0)->after('mrp_calculation_rules');
            $table->unsignedInteger('warranty_months')->nullable()->after('discount_percent');
            $table->text('warranty_terms')->nullable()->after('warranty_months');
            $table->string('catalog_link')->nullable()->after('warranty_terms');
            $table->string('image_path')->nullable()->after('catalog_link');
            $table->enum('tracking_type', ['none', 'serial', 'batch'])->default('none')->after('image_path');
            $table->decimal('min_stock', 14, 4)->default(0)->after('tracking_type');
            $table->decimal('reorder_level', 14, 4)->default(0)->after('min_stock');
            $table->unsignedInteger('aging_threshold_days')->nullable()->after('reorder_level');
            $table->unsignedInteger('credit_period_days')->nullable()->after('aging_threshold_days');
            $table->unsignedInteger('payment_period_days')->nullable()->after('credit_period_days');
            $table->unsignedInteger('lifespan_days')->nullable()->after('payment_period_days');
        });

        Schema::create('product_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('trade_price', 12, 2)->nullable();
            $table->decimal('selling_price', 12, 2)->nullable();
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->decimal('mrp', 12, 2)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30)->default('image');
            $table->string('path');
            $table->string('caption')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::table('price_masters', function (Blueprint $table) {
            $table->date('effective_from')->nullable()->after('min_qty');
            $table->date('effective_to')->nullable()->after('effective_from');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->enum('party_type', ['dealer', 'customer', 'supplier', 'both'])->default('customer')->after('code');
            $table->decimal('credit_limit', 14, 2)->default(0)->after('shipping_gstin');
            $table->unsignedInteger('credit_days')->default(0)->after('credit_limit');
            $table->decimal('interest_rate', 5, 2)->default(18)->after('credit_days');
            $table->enum('credit_period_basis', ['monthly', 'quarterly', 'yearly', 'cumulative'])->default('cumulative')->after('interest_rate');
            $table->string('payment_terms')->nullable()->after('credit_period_basis');
            $table->enum('credit_status', ['open', 'restricted', 'frozen'])->default('open')->after('payment_terms');
            $table->enum('risk_status', ['normal', 'watch', 'high'])->default('normal')->after('credit_status');
            $table->unsignedInteger('cheque_bounce_count')->default(0)->after('risk_status');
            $table->text('credit_notes')->nullable()->after('cheque_bounce_count');
        });

        Schema::create('party_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->enum('level', ['party', 'branch', 'site', 'warehouse', 'transporter', 'driver', 'site_incharge'])->default('party');
            $table->string('name');
            $table->string('role')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('alternate_phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('party_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['billing', 'shipping', 'site', 'warehouse'])->default('billing');
            $table->string('label')->nullable();
            $table->string('name')->nullable();
            $table->text('address')->nullable();
            $table->string('state', 100)->nullable();
            $table->string('pincode', 12)->nullable();
            $table->string('gstin', 20)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('user_branch_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_default')->default(false);
            $table->unique(['user_id', 'branch_id']);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
        });

        Schema::table('document_sequences', function (Blueprint $table) {
            if (! Schema::hasColumn('document_sequences', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('document_sequences', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('document_sequences', 'financial_year_id')) {
                $table->foreignId('financial_year_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
            }
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->string('action', 50);
            $table->nullableMorphs('subject');
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('approval_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->nullableMorphs('approvable');
            $table->string('action', 50);
            $table->string('decision', 30)->nullable();
            $table->text('reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        // Seed default org and backfill
        $now = now();
        $groupId = DB::table('business_groups')->insertGetId([
            'name' => 'Default Group',
            'code' => 'DEFAULT',
            'description' => 'Auto-created business group',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $companyId = DB::table('companies')->insertGetId([
            'business_group_id' => $groupId,
            'name' => 'Default Company',
            'code' => 'MAIN',
            'legal_name' => 'Default Company',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('company_group_links')->insert([
            'business_group_id' => $groupId,
            'company_id' => $companyId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $branchId = DB::table('branches')->insertGetId([
            'company_id' => $companyId,
            'name' => 'Head Office',
            'code' => 'HO',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('warehouses')->insert([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'name' => 'Main Warehouse',
            'code' => 'WH1',
            'is_default' => true,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $fyStart = now()->month >= 4
            ? now()->copy()->month(4)->startOfMonth()->startOfDay()
            : now()->copy()->subYear()->month(4)->startOfMonth()->startOfDay();
        $fyEnd = $fyStart->copy()->addYear()->subDay();

        DB::table('financial_years')->insert([
            'company_id' => $companyId,
            'name' => $fyStart->format('Y').'-'.$fyEnd->format('y'),
            'starts_on' => $fyStart->toDateString(),
            'ends_on' => $fyEnd->toDateString(),
            'is_closed' => false,
            'is_current' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('products')->whereNull('company_id')->update(['company_id' => $companyId]);
        DB::table('customers')->whereNull('company_id')->update([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'party_type' => 'customer',
        ]);
        DB::table('users')->whereNull('company_id')->update([
            'company_id' => $companyId,
            'branch_id' => $branchId,
        ]);

        $userIds = DB::table('users')->pluck('id');
        foreach ($userIds as $userId) {
            DB::table('user_branch_access')->insertOrIgnore([
                'user_id' => $userId,
                'branch_id' => $branchId,
                'is_default' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_logs');
        Schema::dropIfExists('activity_logs');

        Schema::table('document_sequences', function (Blueprint $table) {
            foreach (['financial_year_id', 'branch_id', 'company_id'] as $col) {
                if (Schema::hasColumn('document_sequences', $col)) {
                    $table->dropConstrainedForeignId($col);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'branch_id')) {
                $table->dropConstrainedForeignId('branch_id');
            }
            if (Schema::hasColumn('users', 'company_id')) {
                $table->dropConstrainedForeignId('company_id');
            }
        });

        Schema::dropIfExists('user_branch_access');
        Schema::dropIfExists('party_addresses');
        Schema::dropIfExists('party_contacts');

        Schema::table('customers', function (Blueprint $table) {
            foreach ([
                'credit_notes', 'cheque_bounce_count', 'risk_status', 'credit_status', 'payment_terms',
                'credit_period_basis', 'interest_rate', 'credit_days', 'credit_limit', 'party_type',
                'branch_id', 'company_id',
            ] as $col) {
                if (Schema::hasColumn('customers', $col)) {
                    if (in_array($col, ['branch_id', 'company_id'], true)) {
                        $table->dropConstrainedForeignId($col);
                    } else {
                        $table->dropColumn($col);
                    }
                }
            }
        });

        Schema::table('price_masters', function (Blueprint $table) {
            if (Schema::hasColumn('price_masters', 'effective_from')) {
                $table->dropColumn(['effective_from', 'effective_to']);
            }
        });

        Schema::dropIfExists('product_media');
        Schema::dropIfExists('product_price_histories');

        Schema::table('products', function (Blueprint $table) {
            $dropFk = ['sub_category_id', 'category_id', 'brand_id', 'company_id'];
            foreach ($dropFk as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropConstrainedForeignId($col);
                }
            }
            $cols = [
                'specification', 'trade_price', 'purchase_price', 'calculation_mrp', 'mrp_calculation_rules',
                'discount_percent', 'warranty_months', 'warranty_terms', 'catalog_link', 'image_path',
                'tracking_type', 'min_stock', 'reorder_level', 'aging_threshold_days', 'credit_period_days',
                'payment_period_days', 'lifespan_days',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('sub_categories');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('financial_years');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('company_group_links');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('business_groups');
    }
};
