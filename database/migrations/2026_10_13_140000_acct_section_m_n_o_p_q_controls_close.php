<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('acct_approval_policies')) {
            Schema::create('acct_approval_policies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->string('name', 120);
                $table->string('document_type', 40); // voucher|payment_batch|purchase_order|expense_claim
                $table->decimal('min_amount', 18, 2)->default(0);
                $table->decimal('max_amount', 18, 2)->nullable();
                $table->unsignedTinyInteger('levels')->default(1);
                $table->json('approver_role_ids')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_approval_requests')) {
            Schema::create('acct_approval_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('approval_policy_id')->nullable()->constrained('acct_approval_policies')->nullOnDelete();
                $table->string('document_type', 40);
                $table->unsignedBigInteger('document_id');
                $table->decimal('amount', 18, 2)->default(0);
                $table->unsignedTinyInteger('current_level')->default(1);
                $table->string('status', 20)->default('pending');
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('decided_at')->nullable();
                $table->string('remarks', 500)->nullable();
                $table->timestamps();
                $table->index(['company_id', 'document_type', 'document_id'], 'acct_approval_doc_idx');
            });
        }

        Schema::table('acct_vouchers', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_vouchers', 'approval_status')) {
                $table->string('approval_status', 20)->default('none')->after('status');
            }
            if (! Schema::hasColumn('acct_vouchers', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_vouchers', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });

        if (! Schema::hasTable('acct_close_checklists')) {
            Schema::create('acct_close_checklists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('accounting_period_id')->nullable()->constrained('acct_accounting_periods')->nullOnDelete();
                $table->string('period_label', 40);
                $table->string('kind', 20)->default('month'); // month|year
                $table->string('status', 20)->default('open');
                $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_close_checklist_tasks')) {
            Schema::create('acct_close_checklist_tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('close_checklist_id')->constrained('acct_close_checklists')->cascadeOnDelete();
                $table->string('task_key', 80);
                $table->string('title', 160);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->string('status', 20)->default('pending');
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('completed_at')->nullable();
                $table->string('notes', 500)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_balance_sheet_reconciliations')) {
            Schema::create('acct_balance_sheet_reconciliations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('ledger_id')->constrained('acct_ledgers')->cascadeOnDelete();
                $table->date('as_of_date');
                $table->decimal('gl_balance', 18, 2)->default(0);
                $table->decimal('substantiated_balance', 18, 2)->default(0);
                $table->decimal('difference', 18, 2)->default(0);
                $table->string('status', 20)->default('open');
                $table->string('evidence_note', 500)->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['company_id', 'ledger_id', 'as_of_date'], 'acct_bs_recon_uq');
            });
        }

        if (! Schema::hasTable('acct_kpi_snapshots')) {
            Schema::create('acct_kpi_snapshots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->date('as_of_date');
                $table->decimal('dso', 12, 2)->nullable();
                $table->decimal('dpo', 12, 2)->nullable();
                $table->decimal('inventory_turnover', 12, 4)->nullable();
                $table->decimal('cash_runway_days', 12, 2)->nullable();
                $table->decimal('working_capital', 18, 2)->nullable();
                $table->decimal('gross_margin_percent', 8, 4)->nullable();
                $table->json('extras_json')->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'as_of_date']);
            });
        }

        if (! Schema::hasTable('acct_anomaly_flags')) {
            Schema::create('acct_anomaly_flags', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->string('flag_type', 40); // duplicate_payment|unusual_amount|weekend_posting|etc
                $table->string('document_type', 40)->nullable();
                $table->unsignedBigInteger('document_id')->nullable();
                $table->decimal('amount', 18, 2)->nullable();
                $table->string('severity', 80)->default('medium');
                $table->string('message', 500);
                $table->string('status', 20)->default('open');
                $table->timestamps();
                $table->index(['company_id', 'status', 'flag_type'], 'acct_anomaly_idx');
            });
        }

        if (! Schema::hasTable('acct_integration_endpoints')) {
            Schema::create('acct_integration_endpoints', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->string('name', 120);
                $table->string('kind', 40); // webhook|bank_feed|gateway|crm|payroll|expense
                $table->string('url', 500)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_synced_at')->nullable();
                $table->string('last_error', 500)->nullable();
                $table->unsignedInteger('retry_count')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_custom_fields')) {
            Schema::create('acct_custom_fields', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->string('entity_type', 40);
                $table->string('field_key', 60);
                $table->string('label', 120);
                $table->string('field_type', 20)->default('text');
                $table->boolean('is_required')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['company_id', 'entity_type', 'field_key'], 'acct_custom_field_uq');
            });
        }

        if (! Schema::hasTable('acct_industry_contracts')) {
            Schema::create('acct_industry_contracts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->string('industry', 40); // construction|subscription|retail|education|nonprofit|franchise
                $table->string('contract_number', 40);
                $table->string('name', 160);
                $table->foreignId('party_ledger_id')->nullable()->constrained('acct_ledgers')->nullOnDelete();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->decimal('contract_value', 18, 2)->default(0);
                $table->decimal('retention_percent', 8, 2)->default(0);
                $table->decimal('recognized_revenue', 18, 2)->default(0);
                $table->string('billing_frequency', 20)->nullable();
                $table->string('status', 20)->default('active');
                $table->json('meta_json')->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'contract_number']);
            });
        }

        if (! Schema::hasTable('acct_profitability_snapshots')) {
            Schema::create('acct_profitability_snapshots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->string('dimension', 40); // product|customer|branch|project|segment
                $table->string('dimension_key', 80);
                $table->string('dimension_label', 160);
                $table->date('from_date');
                $table->date('to_date');
                $table->decimal('revenue', 18, 2)->default(0);
                $table->decimal('cost', 18, 2)->default(0);
                $table->decimal('contribution', 18, 2)->default(0);
                $table->decimal('margin_percent', 8, 4)->default(0);
                $table->timestamps();
                $table->index(['company_id', 'dimension', 'to_date'], 'acct_profit_snap_idx');
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'acct_profitability_snapshots',
            'acct_industry_contracts',
            'acct_custom_fields',
            'acct_integration_endpoints',
            'acct_anomaly_flags',
            'acct_kpi_snapshots',
            'acct_balance_sheet_reconciliations',
            'acct_close_checklist_tasks',
            'acct_close_checklists',
            'acct_approval_requests',
            'acct_approval_policies',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('acct_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('acct_vouchers', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }
            foreach (['approval_status', 'approved_at'] as $col) {
                if (Schema::hasColumn('acct_vouchers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
