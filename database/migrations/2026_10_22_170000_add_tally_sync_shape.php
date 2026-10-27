<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acct_companies', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_companies', 'maintain_inventory')) {
                $table->boolean('maintain_inventory')->default(true)->after('use_bill_wise');
            }
            if (! Schema::hasColumn('acct_companies', 'integrate_accounts_with_inventory')) {
                $table->boolean('integrate_accounts_with_inventory')->default(true)->after('maintain_inventory');
            }
            if (! Schema::hasColumn('acct_companies', 'cost_centres_for_inventory')) {
                $table->boolean('cost_centres_for_inventory')->default(false)->after('integrate_accounts_with_inventory');
            }
            if (! Schema::hasColumn('acct_companies', 'enable_gst')) {
                $table->boolean('enable_gst')->default(true)->after('cost_centres_for_inventory');
            }
            if (! Schema::hasColumn('acct_companies', 'enable_tds')) {
                $table->boolean('enable_tds')->default(false)->after('enable_gst');
            }
            if (! Schema::hasColumn('acct_companies', 'enable_tcs')) {
                $table->boolean('enable_tcs')->default(false)->after('enable_tds');
            }
        });

        Schema::table('acct_ledgers', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_ledgers', 'mailing_name')) {
                $table->string('mailing_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('acct_ledgers', 'alias')) {
                $table->string('alias')->nullable()->after('mailing_name');
            }
            if (! Schema::hasColumn('acct_ledgers', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('address');
            }
            if (! Schema::hasColumn('acct_ledgers', 'country')) {
                $table->string('country', 80)->nullable()->after('state');
            }
            if (! Schema::hasColumn('acct_ledgers', 'pincode')) {
                $table->string('pincode', 12)->nullable()->after('country');
            }
        });

        Schema::table('acct_account_groups', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_account_groups', 'affects_gross_profit')) {
                $table->boolean('affects_gross_profit')->default(false)->after('nature');
            }
        });

        DB::table('acct_account_groups')
            ->whereIn('code', ['SALES', 'PURCHASE', 'DIRECT_EXPENSES', 'DIRECT_INCOMES'])
            ->update(['affects_gross_profit' => true]);

        Schema::table('acct_godowns', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_godowns', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('company_id')->constrained('acct_godowns')->restrictOnDelete();
            }
        });

        Schema::table('acct_cost_centres', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_cost_centres', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('cost_category_id')->constrained('acct_cost_centres')->restrictOnDelete();
            }
        });

        Schema::table('acct_units', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_units', 'gst_uqc')) {
                $table->string('gst_uqc', 8)->nullable()->after('symbol');
            }
        });

        Schema::table('acct_products', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_products', 'alias')) {
                $table->string('alias')->nullable()->after('name');
            }
            if (! Schema::hasColumn('acct_products', 'part_number')) {
                $table->string('part_number', 40)->nullable()->after('code');
            }
            if (! Schema::hasColumn('acct_products', 'description')) {
                $table->string('description', 500)->nullable()->after('part_number');
            }
            if (! Schema::hasColumn('acct_products', 'type_of_supply')) {
                $table->string('type_of_supply', 16)->default('goods')->after('description');
            }
        });

        if (! Schema::hasTable('acct_stock_godown_openings')) {
            Schema::create('acct_stock_godown_openings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('acct_products')->cascadeOnDelete();
                $table->foreignId('godown_id')->constrained('acct_godowns')->restrictOnDelete();
                $table->string('batch_name', 40)->default('');
                $table->decimal('quantity', 15, 4);
                $table->decimal('rate', 15, 2)->default(0);
                $table->decimal('value', 15, 2)->default(0);
                $table->timestamps();
                $table->unique(['product_id', 'godown_id', 'batch_name'], 'stock_godown_openings_unique');
            });
        }

        Schema::table('acct_vouchers', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_vouchers', 'party_ledger_id')) {
                $table->foreignId('party_ledger_id')->nullable()->after('voucher_date')->constrained('acct_ledgers')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_vouchers', 'effective_date')) {
                $table->date('effective_date')->nullable()->after('voucher_date');
            }
            if (! Schema::hasColumn('acct_vouchers', 'reference_date')) {
                $table->date('reference_date')->nullable()->after('reference_number');
            }
            if (! Schema::hasColumn('acct_vouchers', 'place_of_supply')) {
                $table->string('place_of_supply', 80)->nullable()->after('reference_date');
            }
            if (! Schema::hasColumn('acct_vouchers', 'is_invoice')) {
                $table->boolean('is_invoice')->default(false)->after('is_optional');
            }
        });

        Schema::table('acct_voucher_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_voucher_entries', 'bank_transaction_type')) {
                $table->string('bank_transaction_type', 30)->nullable()->after('reference');
            }
            if (! Schema::hasColumn('acct_voucher_entries', 'instrument_number')) {
                $table->string('instrument_number', 30)->nullable()->after('bank_transaction_type');
            }
            if (! Schema::hasColumn('acct_voucher_entries', 'instrument_date')) {
                $table->date('instrument_date')->nullable()->after('instrument_number');
            }
            if (! Schema::hasColumn('acct_voucher_entries', 'payment_favouring')) {
                $table->string('payment_favouring', 160)->nullable()->after('instrument_date');
            }
            if (! Schema::hasColumn('acct_voucher_entries', 'bank_name')) {
                $table->string('bank_name', 120)->nullable()->after('payment_favouring');
            }
            if (! Schema::hasColumn('acct_voucher_entries', 'bank_account_number')) {
                $table->string('bank_account_number', 30)->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('acct_voucher_entries', 'bank_ifsc')) {
                $table->string('bank_ifsc', 11)->nullable()->after('bank_account_number');
            }
        });

        if (! Schema::hasTable('acct_voucher_inventory_lines')) {
            Schema::create('acct_voucher_inventory_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('voucher_id')->constrained('acct_vouchers')->cascadeOnDelete();
                $table->unsignedSmallInteger('line_number');
                $table->foreignId('product_id')->constrained('acct_products')->restrictOnDelete();
                $table->foreignId('godown_id')->nullable()->constrained('acct_godowns')->nullOnDelete();
                $table->foreignId('destination_godown_id')->nullable()->constrained('acct_godowns')->nullOnDelete();
                $table->string('batch_name', 40)->nullable();
                $table->decimal('quantity', 15, 4);
                $table->decimal('rate', 15, 4)->default(0);
                $table->decimal('amount', 15, 2)->default(0);
                $table->decimal('discount_amount', 15, 2)->default(0);
                $table->timestamps();
                $table->unique(['voucher_id', 'line_number'], 'voucher_inventory_lines_unique');
            });
        }

        if (! Schema::hasTable('acct_tally_links')) {
            Schema::create('acct_tally_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->string('tally_type', 40);
                $table->string('linkable_type');
                $table->unsignedBigInteger('linkable_id');
                $table->string('guid', 64);
                $table->unsignedBigInteger('alter_id')->nullable();
                $table->unsignedBigInteger('master_id')->nullable();
                $table->string('remote_id', 80)->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'tally_type', 'guid'], 'acct_tally_links_guid');
                $table->unique(['linkable_type', 'linkable_id'], 'acct_tally_links_local');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('acct_tally_links');
        Schema::dropIfExists('acct_voucher_inventory_lines');

        Schema::table('acct_voucher_entries', function (Blueprint $table) {
            foreach (['bank_ifsc', 'bank_account_number', 'bank_name', 'payment_favouring', 'instrument_date', 'instrument_number', 'bank_transaction_type'] as $column) {
                if (Schema::hasColumn('acct_voucher_entries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('acct_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('acct_vouchers', 'party_ledger_id')) {
                $table->dropConstrainedForeignId('party_ledger_id');
            }
            foreach (['is_invoice', 'place_of_supply', 'reference_date', 'effective_date'] as $column) {
                if (Schema::hasColumn('acct_vouchers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('acct_stock_godown_openings');

        Schema::table('acct_products', function (Blueprint $table) {
            foreach (['type_of_supply', 'description', 'part_number', 'alias'] as $column) {
                if (Schema::hasColumn('acct_products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('acct_units', function (Blueprint $table) {
            if (Schema::hasColumn('acct_units', 'gst_uqc')) {
                $table->dropColumn('gst_uqc');
            }
        });

        Schema::table('acct_cost_centres', function (Blueprint $table) {
            if (Schema::hasColumn('acct_cost_centres', 'parent_id')) {
                $table->dropConstrainedForeignId('parent_id');
            }
        });

        Schema::table('acct_godowns', function (Blueprint $table) {
            if (Schema::hasColumn('acct_godowns', 'parent_id')) {
                $table->dropConstrainedForeignId('parent_id');
            }
        });

        Schema::table('acct_account_groups', function (Blueprint $table) {
            if (Schema::hasColumn('acct_account_groups', 'affects_gross_profit')) {
                $table->dropColumn('affects_gross_profit');
            }
        });

        Schema::table('acct_ledgers', function (Blueprint $table) {
            foreach (['pincode', 'country', 'contact_person', 'alias', 'mailing_name'] as $column) {
                if (Schema::hasColumn('acct_ledgers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('acct_companies', function (Blueprint $table) {
            foreach (['enable_tcs', 'enable_tds', 'enable_gst', 'cost_centres_for_inventory', 'integrate_accounts_with_inventory', 'maintain_inventory'] as $column) {
                if (Schema::hasColumn('acct_companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};