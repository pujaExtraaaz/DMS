<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('acct_payment_terms')) {
            Schema::create('acct_payment_terms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->string('name', 80);
                $table->unsignedSmallInteger('days')->default(0);
                $table->boolean('due_on_receipt')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['company_id', 'name']);
            });
        }

        if (! Schema::hasTable('acct_party_addresses')) {
            Schema::create('acct_party_addresses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('party_id')->constrained('acct_parties')->cascadeOnDelete();
                $table->string('label', 80)->nullable();
                $table->string('kind', 20)->default('billing'); // billing|shipping|both
                $table->string('line1', 255);
                $table->string('line2', 255)->nullable();
                $table->string('city', 80)->nullable();
                $table->string('state', 80)->nullable();
                $table->string('country', 80)->nullable();
                $table->string('pincode', 20)->nullable();
                $table->string('gstin', 15)->nullable();
                $table->string('place_of_supply', 80)->nullable();
                $table->boolean('is_default_billing')->default(false);
                $table->boolean('is_default_shipping')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['party_id', 'kind']);
            });
        }

        Schema::table('acct_parties', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_parties', 'payment_term_id')) {
                $table->foreignId('payment_term_id')->nullable()->after('credit_days')
                    ->constrained('acct_payment_terms')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_parties', 'default_discount_percent')) {
                $table->decimal('default_discount_percent', 8, 2)->nullable()->after('payment_term_id');
            }
            if (! Schema::hasColumn('acct_parties', 'salesperson_name')) {
                $table->string('salesperson_name', 120)->nullable()->after('default_discount_percent');
            }
            if (! Schema::hasColumn('acct_parties', 'commission_percent')) {
                $table->decimal('commission_percent', 8, 2)->nullable()->after('salesperson_name');
            }
        });

        Schema::table('acct_ledgers', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_ledgers', 'payment_term_id')) {
                $table->foreignId('payment_term_id')->nullable()->after('credit_days')
                    ->constrained('acct_payment_terms')->nullOnDelete();
            }
        });

        if (! Schema::hasTable('acct_quotations')) {
            Schema::create('acct_quotations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained('acct_branches')->cascadeOnDelete();
                $table->foreignId('financial_year_id')->constrained('acct_financial_years')->cascadeOnDelete();
                $table->string('number', 40);
                $table->date('quote_date');
                $table->date('valid_until')->nullable();
                $table->foreignId('customer_ledger_id')->constrained('acct_ledgers')->cascadeOnDelete();
                $table->foreignId('billing_address_id')->nullable()->constrained('acct_party_addresses')->nullOnDelete();
                $table->foreignId('shipping_address_id')->nullable()->constrained('acct_party_addresses')->nullOnDelete();
                $table->string('reference_number', 80)->nullable();
                $table->string('narration', 500)->nullable();
                $table->string('status', 20)->default('draft'); // draft|sent|accepted|expired|cancelled
                $table->decimal('subtotal', 18, 2)->default(0);
                $table->decimal('discount_total', 18, 2)->default(0);
                $table->decimal('tax_total', 18, 2)->default(0);
                $table->decimal('grand_total', 18, 2)->default(0);
                $table->foreignId('sales_order_id')->nullable()->constrained('acct_sales_orders')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['company_id', 'number']);
            });
        }

        if (! Schema::hasTable('acct_quotation_lines')) {
            Schema::create('acct_quotation_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quotation_id')->constrained('acct_quotations')->cascadeOnDelete();
                $table->unsignedSmallInteger('line_number')->default(1);
                $table->foreignId('product_id')->nullable()->constrained('acct_products')->nullOnDelete();
                $table->string('item_name', 200);
                $table->string('hsn_sac', 20)->nullable();
                $table->decimal('quantity', 18, 4)->default(1);
                $table->decimal('rate', 18, 4)->default(0);
                $table->decimal('discount', 18, 2)->default(0);
                $table->decimal('tax_rate', 8, 2)->default(0);
                $table->decimal('tax_amount', 18, 2)->default(0);
                $table->decimal('line_total', 18, 2)->default(0);
                $table->timestamps();
            });
        }

        Schema::table('acct_sales_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_sales_orders', 'quotation_id')) {
                $table->foreignId('quotation_id')->nullable()->after('created_by')
                    ->constrained('acct_quotations')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_sales_orders', 'billing_address_id')) {
                $table->foreignId('billing_address_id')->nullable()->after('customer_ledger_id')
                    ->constrained('acct_party_addresses')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_sales_orders', 'shipping_address_id')) {
                $table->foreignId('shipping_address_id')->nullable()->after('billing_address_id')
                    ->constrained('acct_party_addresses')->nullOnDelete();
            }
        });

        Schema::table('acct_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_invoices', 'invoice_category')) {
                $table->string('invoice_category', 30)->default('tax_invoice')->after('kind');
            }
            if (! Schema::hasColumn('acct_invoices', 'billing_mode')) {
                $table->string('billing_mode', 20)->default('goods')->after('invoice_category');
            }
            if (! Schema::hasColumn('acct_invoices', 'billing_address_id')) {
                $table->foreignId('billing_address_id')->nullable()->after('party_ledger_id')
                    ->constrained('acct_party_addresses')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_invoices', 'shipping_address_id')) {
                $table->foreignId('shipping_address_id')->nullable()->after('billing_address_id')
                    ->constrained('acct_party_addresses')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_invoices', 'against_invoice_id')) {
                $table->foreignId('against_invoice_id')->nullable()->after('sales_order_id')
                    ->constrained('acct_invoices')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_invoices', 'converted_from_invoice_id')) {
                $table->foreignId('converted_from_invoice_id')->nullable()->after('against_invoice_id')
                    ->constrained('acct_invoices')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_invoices', 'retention_percent')) {
                $table->decimal('retention_percent', 8, 2)->nullable()->after('grand_total');
            }
            if (! Schema::hasColumn('acct_invoices', 'retention_amount')) {
                $table->decimal('retention_amount', 18, 2)->nullable()->after('retention_percent');
            }
            if (! Schema::hasColumn('acct_invoices', 'project_milestone_id')) {
                $table->unsignedBigInteger('project_milestone_id')->nullable()->after('retention_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('acct_invoices', function (Blueprint $table) {
            foreach (['project_milestone_id', 'retention_amount', 'retention_percent'] as $col) {
                if (Schema::hasColumn('acct_invoices', $col)) {
                    $table->dropColumn($col);
                }
            }
            foreach (['converted_from_invoice_id', 'against_invoice_id', 'shipping_address_id', 'billing_address_id'] as $col) {
                if (Schema::hasColumn('acct_invoices', $col)) {
                    $table->dropConstrainedForeignId($col);
                }
            }
            foreach (['billing_mode', 'invoice_category'] as $col) {
                if (Schema::hasColumn('acct_invoices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('acct_sales_orders', function (Blueprint $table) {
            foreach (['shipping_address_id', 'billing_address_id', 'quotation_id'] as $col) {
                if (Schema::hasColumn('acct_sales_orders', $col)) {
                    $table->dropConstrainedForeignId($col);
                }
            }
        });

        Schema::dropIfExists('acct_quotation_lines');
        Schema::dropIfExists('acct_quotations');

        Schema::table('acct_ledgers', function (Blueprint $table) {
            if (Schema::hasColumn('acct_ledgers', 'payment_term_id')) {
                $table->dropConstrainedForeignId('payment_term_id');
            }
        });

        Schema::table('acct_parties', function (Blueprint $table) {
            if (Schema::hasColumn('acct_parties', 'payment_term_id')) {
                $table->dropConstrainedForeignId('payment_term_id');
            }
            foreach (['commission_percent', 'salesperson_name', 'default_discount_percent'] as $col) {
                if (Schema::hasColumn('acct_parties', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('acct_party_addresses');
        Schema::dropIfExists('acct_payment_terms');
    }
};
