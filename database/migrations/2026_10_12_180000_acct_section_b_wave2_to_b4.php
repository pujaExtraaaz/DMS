<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('acct_party_price_lists')) {
            Schema::create('acct_party_price_lists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('party_id')->constrained('acct_parties')->cascadeOnDelete();
                $table->foreignId('price_list_id')->constrained('acct_price_lists')->cascadeOnDelete();
                $table->date('valid_from')->nullable();
                $table->date('valid_to')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['party_id', 'price_list_id'], 'acct_party_price_lists_unique');
            });
        }

        if (! Schema::hasTable('acct_recurring_invoices')) {
            Schema::create('acct_recurring_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained('acct_branches')->cascadeOnDelete();
                $table->string('name', 160);
                $table->foreignId('customer_ledger_id')->constrained('acct_ledgers')->cascadeOnDelete();
                $table->foreignId('account_ledger_id')->nullable()->constrained('acct_ledgers')->nullOnDelete();
                $table->string('frequency', 20)->default('monthly');
                $table->unsignedTinyInteger('day_of_month')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->date('next_run_on')->nullable();
                $table->string('invoice_category', 30)->default('tax_invoice');
                $table->string('narration', 500)->nullable();
                $table->boolean('auto_post')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_recurring_invoice_lines')) {
            Schema::create('acct_recurring_invoice_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('recurring_invoice_id')->constrained('acct_recurring_invoices')->cascadeOnDelete();
                $table->unsignedSmallInteger('line_number')->default(1);
                $table->foreignId('product_id')->nullable()->constrained('acct_products')->nullOnDelete();
                $table->string('item_name', 200);
                $table->decimal('quantity', 18, 4)->default(1);
                $table->decimal('rate', 18, 4)->default(0);
                $table->decimal('discount', 18, 2)->default(0);
                $table->decimal('tax_rate', 8, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_collection_reminders')) {
            Schema::create('acct_collection_reminders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('bill_id')->constrained('acct_bills')->cascadeOnDelete();
                $table->foreignId('ledger_id')->constrained('acct_ledgers')->cascadeOnDelete();
                $table->string('channel', 40)->default('queue');
                $table->string('status', 20)->default('queued');
                $table->string('message', 500)->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
                $table->index(['company_id', 'status']);
            });
        }

        if (! Schema::hasTable('acct_projects')) {
            Schema::create('acct_projects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('customer_ledger_id')->nullable()->constrained('acct_ledgers')->nullOnDelete();
                $table->string('code', 40)->nullable();
                $table->string('name', 160);
                $table->decimal('contract_value', 18, 2)->default(0);
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('status', 20)->default('open');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['company_id', 'name']);
            });
        }

        if (! Schema::hasTable('acct_project_milestones')) {
            Schema::create('acct_project_milestones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('acct_projects')->cascadeOnDelete();
                $table->string('name', 160);
                $table->decimal('percent', 8, 2)->default(0);
                $table->decimal('amount', 18, 2)->default(0);
                $table->date('due_date')->nullable();
                $table->foreignId('billed_invoice_id')->nullable()->constrained('acct_invoices')->nullOnDelete();
                $table->string('status', 20)->default('pending');
                $table->timestamps();
            });
        }

        if (Schema::hasTable('acct_invoices') && Schema::hasColumn('acct_invoices', 'project_milestone_id')) {
            Schema::table('acct_invoices', function (Blueprint $table) {
                try {
                    $table->foreign('project_milestone_id')
                        ->references('id')->on('acct_project_milestones')->nullOnDelete();
                } catch (\Throwable) {
                    // FK may already exist
                }
            });
        }

        if (! Schema::hasTable('acct_deferred_revenue_schedules')) {
            Schema::create('acct_deferred_revenue_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('invoice_id')->nullable()->constrained('acct_invoices')->nullOnDelete();
                $table->foreignId('customer_ledger_id')->constrained('acct_ledgers')->cascadeOnDelete();
                $table->foreignId('revenue_ledger_id')->nullable()->constrained('acct_ledgers')->nullOnDelete();
                $table->foreignId('deferred_ledger_id')->nullable()->constrained('acct_ledgers')->nullOnDelete();
                $table->decimal('total_amount', 18, 2);
                $table->decimal('recognized_amount', 18, 2)->default(0);
                $table->date('start_date');
                $table->date('end_date');
                $table->string('frequency', 20)->default('monthly');
                $table->date('next_recognize_on')->nullable();
                $table->string('status', 20)->default('open');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_sales_commissions')) {
            Schema::create('acct_sales_commissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('invoice_id')->constrained('acct_invoices')->cascadeOnDelete();
                $table->foreignId('party_id')->nullable()->constrained('acct_parties')->nullOnDelete();
                $table->string('salesperson_name', 120)->nullable();
                $table->decimal('base_amount', 18, 2);
                $table->decimal('percent', 8, 2);
                $table->decimal('commission_amount', 18, 2);
                $table->foreignId('accrual_voucher_id')->nullable()->constrained('acct_vouchers')->nullOnDelete();
                $table->string('status', 20)->default('accrued');
                $table->timestamps();
            });
        }

        Schema::table('acct_companies', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_companies', 'customer_advance_ledger_id')) {
                $table->foreignId('customer_advance_ledger_id')->nullable()
                    ->constrained('acct_ledgers')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_companies', 'bad_debt_ledger_id')) {
                $table->foreignId('bad_debt_ledger_id')->nullable()
                    ->constrained('acct_ledgers')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_companies', 'doubtful_debt_ledger_id')) {
                $table->foreignId('doubtful_debt_ledger_id')->nullable()
                    ->constrained('acct_ledgers')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_companies', 'retention_ledger_id')) {
                $table->foreignId('retention_ledger_id')->nullable()
                    ->constrained('acct_ledgers')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_companies', 'commission_expense_ledger_id')) {
                $table->foreignId('commission_expense_ledger_id')->nullable()
                    ->constrained('acct_ledgers')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_companies', 'commission_payable_ledger_id')) {
                $table->foreignId('commission_payable_ledger_id')->nullable()
                    ->constrained('acct_ledgers')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_companies', 'collection_reminder_days_before')) {
                $table->unsignedSmallInteger('collection_reminder_days_before')->nullable();
            }
            if (! Schema::hasColumn('acct_companies', 'collection_reminder_days_after')) {
                $table->unsignedSmallInteger('collection_reminder_days_after')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('acct_companies', function (Blueprint $table) {
            foreach ([
                'commission_payable_ledger_id', 'commission_expense_ledger_id', 'retention_ledger_id',
                'doubtful_debt_ledger_id', 'bad_debt_ledger_id', 'customer_advance_ledger_id',
            ] as $col) {
                if (Schema::hasColumn('acct_companies', $col)) {
                    $table->dropConstrainedForeignId($col);
                }
            }
            foreach (['collection_reminder_days_after', 'collection_reminder_days_before'] as $col) {
                if (Schema::hasColumn('acct_companies', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('acct_sales_commissions');
        Schema::dropIfExists('acct_deferred_revenue_schedules');

        if (Schema::hasColumn('acct_invoices', 'project_milestone_id')) {
            Schema::table('acct_invoices', function (Blueprint $table) {
                try {
                    $table->dropForeign(['project_milestone_id']);
                } catch (\Throwable) {
                }
            });
        }

        Schema::dropIfExists('acct_project_milestones');
        Schema::dropIfExists('acct_projects');
        Schema::dropIfExists('acct_collection_reminders');
        Schema::dropIfExists('acct_recurring_invoice_lines');
        Schema::dropIfExists('acct_recurring_invoices');
        Schema::dropIfExists('acct_party_price_lists');
    }
};
