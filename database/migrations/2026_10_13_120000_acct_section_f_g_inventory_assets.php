<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acct_companies', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_companies', 'inventory_valuation_method')) {
                $table->string('inventory_valuation_method', 40)->default('weighted_average')->after('accounting_basis');
            }
        });

        if (! Schema::hasTable('acct_stock_counts')) {
            Schema::create('acct_stock_counts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('acct_branches')->nullOnDelete();
                $table->foreignId('godown_id')->nullable()->constrained('acct_godowns')->nullOnDelete();
                $table->string('number', 40);
                $table->date('count_date');
                $table->string('status', 20)->default('draft');
                $table->string('narration', 500)->nullable();
                $table->foreignId('adjustment_voucher_id')->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'number']);
            });
        }

        if (! Schema::hasTable('acct_stock_count_lines')) {
            Schema::create('acct_stock_count_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stock_count_id')->constrained('acct_stock_counts')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('acct_products')->cascadeOnDelete();
                $table->decimal('system_qty', 18, 4)->default(0);
                $table->decimal('counted_qty', 18, 4)->default(0);
                $table->decimal('variance_qty', 18, 4)->default(0);
                $table->decimal('unit_cost', 18, 4)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_goods_in_transit')) {
            Schema::create('acct_goods_in_transit', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('from_godown_id')->nullable()->constrained('acct_godowns')->nullOnDelete();
                $table->foreignId('to_godown_id')->nullable()->constrained('acct_godowns')->nullOnDelete();
                $table->foreignId('product_id')->constrained('acct_products')->cascadeOnDelete();
                $table->decimal('quantity', 18, 4)->default(0);
                $table->date('dispatch_date');
                $table->date('expected_receipt_date')->nullable();
                $table->date('received_date')->nullable();
                $table->string('status', 20)->default('in_transit');
                $table->string('reference', 80)->nullable();
                $table->boolean('is_consignment')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_job_works')) {
            Schema::create('acct_job_works', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('acct_branches')->nullOnDelete();
                $table->string('number', 40);
                $table->date('issue_date');
                $table->foreignId('vendor_ledger_id')->constrained('acct_ledgers')->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('acct_products')->nullOnDelete();
                $table->decimal('issued_qty', 18, 4)->default(0);
                $table->decimal('received_qty', 18, 4)->default(0);
                $table->decimal('job_charges', 18, 2)->default(0);
                $table->string('status', 20)->default('open');
                $table->string('narration', 500)->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'number']);
            });
        }

        if (! Schema::hasTable('acct_fixed_asset_categories')) {
            Schema::create('acct_fixed_asset_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->string('name', 120);
                $table->string('depreciation_method', 40)->default('straight_line'); // straight_line|wdv
                $table->decimal('useful_life_years', 8, 2)->default(5);
                $table->decimal('residual_percent', 8, 2)->default(5);
                $table->foreignId('asset_ledger_id')->nullable()->constrained('acct_ledgers')->nullOnDelete();
                $table->foreignId('accum_depr_ledger_id')->nullable()->constrained('acct_ledgers')->nullOnDelete();
                $table->foreignId('depr_expense_ledger_id')->nullable()->constrained('acct_ledgers')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['company_id', 'name']);
            });
        }

        if (! Schema::hasTable('acct_fixed_assets')) {
            Schema::create('acct_fixed_assets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('category_id')->nullable()->constrained('acct_fixed_asset_categories')->nullOnDelete();
                $table->string('asset_code', 40);
                $table->string('name', 160);
                $table->date('acquisition_date');
                $table->date('capitalization_date')->nullable();
                $table->decimal('gross_value', 18, 2)->default(0);
                $table->decimal('residual_value', 18, 2)->default(0);
                $table->decimal('accumulated_depreciation', 18, 2)->default(0);
                $table->decimal('book_value', 18, 2)->default(0);
                $table->string('location', 120)->nullable();
                $table->string('department', 120)->nullable();
                $table->string('custodian', 120)->nullable();
                $table->boolean('is_cip')->default(false);
                $table->string('status', 20)->default('active'); // active|disposed|impaired|transferred
                $table->date('disposal_date')->nullable();
                $table->decimal('disposal_proceeds', 18, 2)->nullable();
                $table->decimal('disposal_gain_loss', 18, 2)->nullable();
                $table->foreignId('parent_asset_id')->nullable()->constrained('acct_fixed_assets')->nullOnDelete();
                $table->timestamps();
                $table->unique(['company_id', 'asset_code']);
            });
        }

        if (! Schema::hasTable('acct_depreciation_entries')) {
            Schema::create('acct_depreciation_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('fixed_asset_id')->constrained('acct_fixed_assets')->cascadeOnDelete();
                $table->string('period', 7); // YYYY-MM
                $table->date('entry_date');
                $table->decimal('amount', 18, 2)->default(0);
                $table->foreignId('voucher_id')->nullable()->constrained('acct_vouchers')->nullOnDelete();
                $table->string('method', 40)->nullable();
                $table->timestamps();
                $table->unique(['fixed_asset_id', 'period'], 'acct_depr_asset_period_uq');
            });
        }

        if (! Schema::hasTable('acct_asset_verifications')) {
            Schema::create('acct_asset_verifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('fixed_asset_id')->constrained('acct_fixed_assets')->cascadeOnDelete();
                $table->date('verification_date');
                $table->string('verified_by', 120)->nullable();
                $table->string('condition', 40)->default('good');
                $table->boolean('found')->default(true);
                $table->string('notes', 500)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'acct_asset_verifications',
            'acct_depreciation_entries',
            'acct_fixed_assets',
            'acct_fixed_asset_categories',
            'acct_job_works',
            'acct_goods_in_transit',
            'acct_stock_count_lines',
            'acct_stock_counts',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('acct_companies', function (Blueprint $table) {
            if (Schema::hasColumn('acct_companies', 'inventory_valuation_method')) {
                $table->dropColumn('inventory_valuation_method');
            }
        });
    }
};
