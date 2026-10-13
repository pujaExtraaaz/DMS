<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('acct_landed_cost_allocations')) {
            Schema::create('acct_landed_cost_allocations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('landed_cost_voucher_id')->constrained('acct_landed_cost_vouchers')->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('acct_products')->nullOnDelete();
                $table->string('item_name', 200)->nullable();
                $table->decimal('quantity', 18, 4)->default(0);
                $table->decimal('base_value', 18, 2)->default(0);
                $table->decimal('allocated_charges', 18, 2)->default(0);
                $table->decimal('landed_unit_cost', 18, 4)->default(0);
                $table->timestamps();
            });
        }

        Schema::table('acct_landed_cost_vouchers', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_landed_cost_vouchers', 'voucher_id')) {
                $table->foreignId('voucher_id')->nullable()->after('status')
                    ->constrained('acct_vouchers')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_landed_cost_vouchers', 'posted_at')) {
                $table->timestamp('posted_at')->nullable()->after('voucher_id');
            }
        });

        Schema::table('acct_expense_claims', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_expense_claims', 'accrual_voucher_id')) {
                $table->foreignId('accrual_voucher_id')->nullable()->after('payment_voucher_id')
                    ->constrained('acct_vouchers')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_expense_claims', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('status')
                    ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_expense_claims', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });

        if (! Schema::hasTable('acct_stock_cost_layers')) {
            Schema::create('acct_stock_cost_layers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('acct_products')->cascadeOnDelete();
                $table->foreignId('godown_id')->nullable()->constrained('acct_godowns')->nullOnDelete();
                $table->foreignId('stock_movement_id')->nullable()->constrained('acct_stock_movements')->nullOnDelete();
                $table->date('received_on');
                $table->decimal('quantity_in', 18, 4)->default(0);
                $table->decimal('quantity_remaining', 18, 4)->default(0);
                $table->decimal('unit_cost', 18, 4)->default(0);
                $table->timestamps();
                $table->index(['company_id', 'product_id', 'godown_id', 'received_on'], 'acct_fifo_layer_idx');
            });
        }

        if (! Schema::hasTable('acct_stock_cost_consumptions')) {
            Schema::create('acct_stock_cost_consumptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('stock_cost_layer_id')->constrained('acct_stock_cost_layers')->cascadeOnDelete();
                $table->foreignId('stock_movement_id')->nullable()->constrained('acct_stock_movements')->nullOnDelete();
                $table->decimal('quantity', 18, 4)->default(0);
                $table->decimal('unit_cost', 18, 4)->default(0);
                $table->decimal('value', 18, 2)->default(0);
                $table->date('consumed_on');
                $table->timestamps();
            });
        }

        Schema::table('acct_loans', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_loans', 'disbursement_voucher_id')) {
                $table->foreignId('disbursement_voucher_id')->nullable()
                    ->constrained('acct_vouchers')->nullOnDelete();
            }
        });

        Schema::table('acct_vendor_retentions', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_vendor_retentions', 'hold_voucher_id')) {
                $table->foreignId('hold_voucher_id')->nullable()
                    ->constrained('acct_vouchers')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_vendor_retentions', 'release_voucher_id')) {
                $table->foreignId('release_voucher_id')->nullable()
                    ->constrained('acct_vouchers')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_vendor_retentions', 'retention_ledger_id')) {
                $table->foreignId('retention_ledger_id')->nullable()
                    ->constrained('acct_ledgers')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('acct_vendor_retentions', function (Blueprint $table) {
            foreach (['retention_ledger_id', 'release_voucher_id', 'hold_voucher_id'] as $col) {
                if (Schema::hasColumn('acct_vendor_retentions', $col)) {
                    $table->dropConstrainedForeignId($col);
                }
            }
        });

        Schema::table('acct_loans', function (Blueprint $table) {
            if (Schema::hasColumn('acct_loans', 'disbursement_voucher_id')) {
                $table->dropConstrainedForeignId('disbursement_voucher_id');
            }
        });

        Schema::dropIfExists('acct_stock_cost_consumptions');
        Schema::dropIfExists('acct_stock_cost_layers');

        Schema::table('acct_expense_claims', function (Blueprint $table) {
            if (Schema::hasColumn('acct_expense_claims', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('acct_expense_claims', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }
            if (Schema::hasColumn('acct_expense_claims', 'accrual_voucher_id')) {
                $table->dropConstrainedForeignId('accrual_voucher_id');
            }
        });

        Schema::table('acct_landed_cost_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('acct_landed_cost_vouchers', 'posted_at')) {
                $table->dropColumn('posted_at');
            }
            if (Schema::hasColumn('acct_landed_cost_vouchers', 'voucher_id')) {
                $table->dropConstrainedForeignId('voucher_id');
            }
        });

        Schema::dropIfExists('acct_landed_cost_allocations');
    }
};
