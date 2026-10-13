<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('acct_purchase_requisitions')) {
            Schema::create('acct_purchase_requisitions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained('acct_branches')->cascadeOnDelete();
                $table->foreignId('financial_year_id')->constrained('acct_financial_years')->cascadeOnDelete();
                $table->string('number', 40);
                $table->date('requisition_date');
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained('acct_departments')->nullOnDelete();
                $table->string('priority', 20)->default('normal');
                $table->string('status', 20)->default('draft');
                $table->string('narration', 500)->nullable();
                $table->foreignId('purchase_order_id')->nullable()->constrained('acct_purchase_orders')->nullOnDelete();
                $table->timestamps();
                $table->unique(['company_id', 'number']);
            });
        }

        if (! Schema::hasTable('acct_purchase_requisition_lines')) {
            Schema::create('acct_purchase_requisition_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_requisition_id')->constrained('acct_purchase_requisitions')->cascadeOnDelete();
                $table->unsignedSmallInteger('line_number')->default(1);
                $table->foreignId('product_id')->nullable()->constrained('acct_products')->nullOnDelete();
                $table->string('item_name', 200);
                $table->decimal('quantity', 18, 4)->default(1);
                $table->string('unit', 40)->nullable();
                $table->date('required_by')->nullable();
                $table->string('notes', 255)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_request_for_quotations')) {
            Schema::create('acct_request_for_quotations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained('acct_branches')->cascadeOnDelete();
                $table->foreignId('financial_year_id')->constrained('acct_financial_years')->cascadeOnDelete();
                $table->string('number', 40);
                $table->date('rfq_date');
                $table->date('due_date')->nullable();
                $table->foreignId('purchase_requisition_id')->nullable()->constrained('acct_purchase_requisitions')->nullOnDelete();
                $table->string('status', 20)->default('draft');
                $table->string('narration', 500)->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['company_id', 'number']);
            });
        }

        if (! Schema::hasTable('acct_request_for_quotation_lines')) {
            Schema::create('acct_request_for_quotation_lines', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('request_for_quotation_id');
                $table->unsignedSmallInteger('line_number')->default(1);
                $table->foreignId('product_id')->nullable()->constrained('acct_products')->nullOnDelete();
                $table->string('item_name', 200);
                $table->decimal('quantity', 18, 4)->default(1);
                $table->string('unit', 40)->nullable();
                $table->timestamps();
                $table->foreign('request_for_quotation_id', 'acct_rfq_lines_rfq_fk')
                    ->references('id')->on('acct_request_for_quotations')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('acct_vendor_quotations')) {
            Schema::create('acct_vendor_quotations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->unsignedBigInteger('request_for_quotation_id');
                $table->foreignId('supplier_ledger_id')->constrained('acct_ledgers')->cascadeOnDelete();
                $table->date('quote_date');
                $table->date('valid_until')->nullable();
                $table->decimal('subtotal', 18, 2)->default(0);
                $table->decimal('tax_total', 18, 2)->default(0);
                $table->decimal('grand_total', 18, 2)->default(0);
                $table->unsignedSmallInteger('lead_days')->default(0);
                $table->string('payment_terms_note', 120)->nullable();
                $table->boolean('is_selected')->default(false);
                $table->string('status', 20)->default('received');
                $table->string('notes', 500)->nullable();
                $table->timestamps();
                $table->foreign('request_for_quotation_id', 'acct_vendor_quotes_rfq_fk')
                    ->references('id')->on('acct_request_for_quotations')->cascadeOnDelete();
                $table->unique(['request_for_quotation_id', 'supplier_ledger_id'], 'acct_vendor_quotes_rfq_supplier_uq');
            });
        }

        if (! Schema::hasTable('acct_vendor_quotation_lines')) {
            Schema::create('acct_vendor_quotation_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_quotation_id')->constrained('acct_vendor_quotations')->cascadeOnDelete();
                $table->unsignedSmallInteger('line_number')->default(1);
                $table->foreignId('product_id')->nullable()->constrained('acct_products')->nullOnDelete();
                $table->string('item_name', 200);
                $table->decimal('quantity', 18, 4)->default(1);
                $table->decimal('rate', 18, 4)->default(0);
                $table->decimal('tax_rate', 8, 2)->default(0);
                $table->decimal('line_total', 18, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_goods_receipts')) {
            Schema::create('acct_goods_receipts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained('acct_branches')->cascadeOnDelete();
                $table->foreignId('financial_year_id')->constrained('acct_financial_years')->cascadeOnDelete();
                $table->string('number', 40);
                $table->date('receipt_date');
                $table->foreignId('purchase_order_id')->nullable()->constrained('acct_purchase_orders')->nullOnDelete();
                $table->foreignId('supplier_ledger_id')->constrained('acct_ledgers')->cascadeOnDelete();
                $table->foreignId('godown_id')->nullable()->constrained('acct_godowns')->nullOnDelete();
                $table->string('receipt_kind', 20)->default('goods'); // goods|service
                $table->string('status', 20)->default('draft');
                $table->boolean('is_invoiced')->default(false);
                $table->foreignId('purchase_invoice_id')->nullable()->constrained('acct_invoices')->nullOnDelete();
                $table->string('narration', 500)->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['company_id', 'number']);
            });
        }

        if (! Schema::hasTable('acct_goods_receipt_lines')) {
            Schema::create('acct_goods_receipt_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('goods_receipt_id')->constrained('acct_goods_receipts')->cascadeOnDelete();
                $table->unsignedSmallInteger('line_number')->default(1);
                $table->foreignId('product_id')->nullable()->constrained('acct_products')->nullOnDelete();
                $table->string('item_name', 200);
                $table->decimal('ordered_qty', 18, 4)->default(0);
                $table->decimal('received_qty', 18, 4)->default(0);
                $table->decimal('rate', 18, 4)->default(0);
                $table->decimal('line_total', 18, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_expense_claims')) {
            Schema::create('acct_expense_claims', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained('acct_branches')->cascadeOnDelete();
                $table->foreignId('financial_year_id')->constrained('acct_financial_years')->cascadeOnDelete();
                $table->string('number', 40);
                $table->date('claim_date');
                $table->foreignId('employee_id')->nullable()->constrained('acct_employees')->nullOnDelete();
                $table->foreignId('employee_ledger_id')->nullable()->constrained('acct_ledgers')->nullOnDelete();
                $table->string('category', 80)->nullable();
                $table->decimal('amount', 18, 2)->default(0);
                $table->string('status', 20)->default('draft');
                $table->boolean('is_reimbursement')->default(true);
                $table->string('policy_flag', 40)->nullable();
                $table->string('narration', 500)->nullable();
                $table->foreignId('payment_voucher_id')->nullable()->constrained('acct_vouchers')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['company_id', 'number']);
            });
        }

        if (! Schema::hasTable('acct_expense_claim_lines')) {
            Schema::create('acct_expense_claim_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('expense_claim_id')->constrained('acct_expense_claims')->cascadeOnDelete();
                $table->unsignedSmallInteger('line_number')->default(1);
                $table->date('expense_date')->nullable();
                $table->string('category', 80)->nullable();
                $table->foreignId('expense_ledger_id')->nullable()->constrained('acct_ledgers')->nullOnDelete();
                $table->string('description', 255)->nullable();
                $table->decimal('amount', 18, 2)->default(0);
                $table->boolean('bill_attached')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_landed_cost_vouchers')) {
            Schema::create('acct_landed_cost_vouchers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained('acct_branches')->cascadeOnDelete();
                $table->foreignId('financial_year_id')->constrained('acct_financial_years')->cascadeOnDelete();
                $table->string('number', 40);
                $table->date('voucher_date');
                $table->foreignId('purchase_invoice_id')->nullable()->constrained('acct_invoices')->nullOnDelete();
                $table->foreignId('goods_receipt_id')->nullable()->constrained('acct_goods_receipts')->nullOnDelete();
                $table->decimal('freight', 18, 2)->default(0);
                $table->decimal('duty', 18, 2)->default(0);
                $table->decimal('other_charges', 18, 2)->default(0);
                $table->decimal('total_charges', 18, 2)->default(0);
                $table->string('allocation_basis', 40)->default('value'); // value|qty
                $table->string('status', 20)->default('draft');
                $table->string('narration', 500)->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'number']);
            });
        }

        if (! Schema::hasTable('acct_prepaid_expenses')) {
            Schema::create('acct_prepaid_expenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('acct_branches')->nullOnDelete();
                $table->string('name', 120);
                $table->foreignId('prepaid_ledger_id')->constrained('acct_ledgers')->cascadeOnDelete();
                $table->foreignId('expense_ledger_id')->constrained('acct_ledgers')->cascadeOnDelete();
                $table->date('start_date');
                $table->date('end_date');
                $table->decimal('total_amount', 18, 2)->default(0);
                $table->decimal('recognized_amount', 18, 2)->default(0);
                $table->string('frequency', 20)->default('monthly');
                $table->string('status', 20)->default('active');
                $table->foreignId('source_voucher_id')->nullable()->constrained('acct_vouchers')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_accrued_expenses')) {
            Schema::create('acct_accrued_expenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('acct_branches')->nullOnDelete();
                $table->string('name', 120);
                $table->date('accrual_date');
                $table->foreignId('expense_ledger_id')->constrained('acct_ledgers')->cascadeOnDelete();
                $table->foreignId('accrual_ledger_id')->constrained('acct_ledgers')->cascadeOnDelete();
                $table->decimal('amount', 18, 2)->default(0);
                $table->foreignId('goods_receipt_id')->nullable()->constrained('acct_goods_receipts')->nullOnDelete();
                $table->boolean('is_uninvoiced_receipt')->default(false);
                $table->boolean('is_reversed')->default(false);
                $table->foreignId('accrual_voucher_id')->nullable()->constrained('acct_vouchers')->nullOnDelete();
                $table->foreignId('reversal_voucher_id')->nullable()->constrained('acct_vouchers')->nullOnDelete();
                $table->string('status', 20)->default('open');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_employee_advances')) {
            Schema::create('acct_employee_advances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('employee_id')->nullable()->constrained('acct_employees')->nullOnDelete();
                $table->foreignId('employee_ledger_id')->constrained('acct_ledgers')->cascadeOnDelete();
                $table->date('advance_date');
                $table->decimal('amount', 18, 2)->default(0);
                $table->decimal('settled_amount', 18, 2)->default(0);
                $table->string('purpose', 255)->nullable();
                $table->string('status', 20)->default('open');
                $table->foreignId('payment_voucher_id')->nullable()->constrained('acct_vouchers')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_payment_batches')) {
            Schema::create('acct_payment_batches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('acct_branches')->nullOnDelete();
                $table->string('number', 40);
                $table->date('batch_date');
                $table->foreignId('bank_ledger_id')->nullable()->constrained('acct_ledgers')->nullOnDelete();
                $table->decimal('total_amount', 18, 2)->default(0);
                $table->string('status', 20)->default('draft'); // draft|pending_approval|approved|paid|rejected
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->string('narration', 500)->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'number']);
            });
        }

        if (! Schema::hasTable('acct_payment_batch_lines')) {
            Schema::create('acct_payment_batch_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_batch_id')->constrained('acct_payment_batches')->cascadeOnDelete();
                $table->foreignId('supplier_ledger_id')->constrained('acct_ledgers')->cascadeOnDelete();
                $table->foreignId('bill_id')->nullable()->constrained('acct_bills')->nullOnDelete();
                $table->decimal('amount', 18, 2)->default(0);
                $table->decimal('tds_amount', 18, 2)->default(0);
                $table->string('reference', 80)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_vendor_retentions')) {
            Schema::create('acct_vendor_retentions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('supplier_ledger_id')->constrained('acct_ledgers')->cascadeOnDelete();
                $table->foreignId('invoice_id')->nullable()->constrained('acct_invoices')->nullOnDelete();
                $table->decimal('retention_percent', 8, 2)->default(0);
                $table->decimal('retention_amount', 18, 2)->default(0);
                $table->decimal('released_amount', 18, 2)->default(0);
                $table->date('retention_date');
                $table->date('release_due_date')->nullable();
                $table->string('kind', 40)->default('retention'); // retention|security_deposit
                $table->string('status', 20)->default('held');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_duplicate_bill_checks')) {
            Schema::create('acct_duplicate_bill_checks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('supplier_ledger_id')->constrained('acct_ledgers')->cascadeOnDelete();
                $table->string('bill_number', 80);
                $table->date('bill_date')->nullable();
                $table->decimal('amount', 18, 2)->default(0);
                $table->foreignId('invoice_id')->nullable()->constrained('acct_invoices')->nullOnDelete();
                $table->string('match_reason', 120)->nullable();
                $table->string('status', 20)->default('flagged');
                $table->timestamps();
                $table->index(['company_id', 'supplier_ledger_id', 'bill_number'], 'acct_dup_bill_lookup_idx');
            });
        }

        Schema::table('acct_purchase_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_purchase_orders', 'purchase_requisition_id')) {
                $table->foreignId('purchase_requisition_id')->nullable()->after('created_by')
                    ->constrained('acct_purchase_requisitions')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_purchase_orders', 'vendor_quotation_id')) {
                $table->foreignId('vendor_quotation_id')->nullable()->after('purchase_requisition_id')
                    ->constrained('acct_vendor_quotations')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_purchase_orders', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_purchase_orders', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });

        Schema::table('acct_parties', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_parties', 'is_msme')) {
                $table->boolean('is_msme')->default(false)->after('is_active');
            }
            if (! Schema::hasColumn('acct_parties', 'msme_registration_number')) {
                $table->string('msme_registration_number', 40)->nullable()->after('is_msme');
            }
            if (! Schema::hasColumn('acct_parties', 'msme_type')) {
                $table->string('msme_type', 40)->nullable()->after('msme_registration_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('acct_purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('acct_purchase_orders', 'vendor_quotation_id')) {
                $table->dropConstrainedForeignId('vendor_quotation_id');
            }
            if (Schema::hasColumn('acct_purchase_orders', 'purchase_requisition_id')) {
                $table->dropConstrainedForeignId('purchase_requisition_id');
            }
            if (Schema::hasColumn('acct_purchase_orders', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }
            if (Schema::hasColumn('acct_purchase_orders', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
        });

        Schema::table('acct_parties', function (Blueprint $table) {
            foreach (['msme_type', 'msme_registration_number', 'is_msme'] as $col) {
                if (Schema::hasColumn('acct_parties', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        foreach ([
            'acct_duplicate_bill_checks',
            'acct_vendor_retentions',
            'acct_payment_batch_lines',
            'acct_payment_batches',
            'acct_employee_advances',
            'acct_accrued_expenses',
            'acct_prepaid_expenses',
            'acct_landed_cost_vouchers',
            'acct_expense_claim_lines',
            'acct_expense_claims',
            'acct_goods_receipt_lines',
            'acct_goods_receipts',
            'acct_vendor_quotation_lines',
            'acct_vendor_quotations',
            'acct_request_for_quotation_lines',
            'acct_request_for_quotations',
            'acct_purchase_requisition_lines',
            'acct_purchase_requisitions',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
