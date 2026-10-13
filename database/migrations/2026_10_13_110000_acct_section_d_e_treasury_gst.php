<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('acct_bank_categorization_rules')) {
            Schema::create('acct_bank_categorization_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->string('name', 120);
                $table->string('match_field', 40)->default('narration'); // narration|reference|amount
                $table->string('match_type', 20)->default('contains'); // contains|equals|starts_with|regex
                $table->string('match_value', 255);
                $table->foreignId('ledger_id')->constrained('acct_ledgers')->cascadeOnDelete();
                $table->unsignedSmallInteger('priority')->default(100);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_electronic_payments')) {
            Schema::create('acct_electronic_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('bank_account_id')->nullable()->constrained('acct_bank_accounts')->nullOnDelete();
                $table->foreignId('voucher_id')->nullable()->constrained('acct_vouchers')->nullOnDelete();
                $table->string('channel', 20); // neft|rtgs|imps|upi|card|gateway
                $table->string('reference', 80)->nullable();
                $table->string('utr', 80)->nullable();
                $table->date('payment_date');
                $table->decimal('amount', 18, 2)->default(0);
                $table->string('beneficiary', 160)->nullable();
                $table->string('status', 20)->default('initiated');
                $table->timestamp('cleared_at')->nullable();
                $table->timestamps();
                $table->index(['company_id', 'channel', 'status']);
            });
        }

        if (! Schema::hasTable('acct_cash_forecasts')) {
            Schema::create('acct_cash_forecasts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->string('name', 120);
                $table->date('as_of_date');
                $table->unsignedSmallInteger('horizon_days')->default(30);
                $table->decimal('opening_cash', 18, 2)->default(0);
                $table->decimal('projected_inflows', 18, 2)->default(0);
                $table->decimal('projected_outflows', 18, 2)->default(0);
                $table->decimal('closing_cash', 18, 2)->default(0);
                $table->string('scenario', 40)->default('base');
                $table->json('lines_json')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_bank_signatory_limits')) {
            Schema::create('acct_bank_signatory_limits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('bank_account_id')->nullable()->constrained('acct_bank_accounts')->nullOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('single_limit', 18, 2)->default(0);
                $table->decimal('daily_limit', 18, 2)->default(0);
                $table->boolean('requires_dual')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['bank_account_id', 'user_id'], 'acct_bank_signatory_uq');
            });
        }

        if (! Schema::hasTable('acct_gstr2b_rows')) {
            Schema::create('acct_gstr2b_rows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->string('return_period', 7); // YYYY-MM
                $table->string('supplier_gstin', 15)->nullable();
                $table->string('supplier_name', 160)->nullable();
                $table->string('invoice_number', 80);
                $table->date('invoice_date')->nullable();
                $table->decimal('taxable_value', 18, 2)->default(0);
                $table->decimal('igst', 18, 2)->default(0);
                $table->decimal('cgst', 18, 2)->default(0);
                $table->decimal('sgst', 18, 2)->default(0);
                $table->decimal('cess', 18, 2)->default(0);
                $table->foreignId('matched_invoice_id')->nullable()->constrained('acct_invoices')->nullOnDelete();
                $table->string('match_status', 20)->default('unmatched'); // unmatched|matched|partial|missing_in_books
                $table->timestamps();
                $table->index(['company_id', 'return_period', 'match_status'], 'acct_gstr2b_period_idx');
            });
        }

        if (! Schema::hasTable('acct_itc_entries')) {
            Schema::create('acct_itc_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('invoice_id')->nullable()->constrained('acct_invoices')->nullOnDelete();
                $table->string('return_period', 7)->nullable();
                $table->string('eligibility', 40)->default('eligible'); // eligible|ineligible|blocked|reversed
                $table->decimal('igst', 18, 2)->default(0);
                $table->decimal('cgst', 18, 2)->default(0);
                $table->decimal('sgst', 18, 2)->default(0);
                $table->decimal('cess', 18, 2)->default(0);
                $table->string('reason', 255)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_gst_challans')) {
            Schema::create('acct_gst_challans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->string('return_period', 7);
                $table->string('challan_number', 40)->nullable();
                $table->date('payment_date');
                $table->decimal('cgst', 18, 2)->default(0);
                $table->decimal('sgst', 18, 2)->default(0);
                $table->decimal('igst', 18, 2)->default(0);
                $table->decimal('cess', 18, 2)->default(0);
                $table->decimal('interest', 18, 2)->default(0);
                $table->decimal('penalty', 18, 2)->default(0);
                $table->decimal('total_amount', 18, 2)->default(0);
                $table->foreignId('payment_voucher_id')->nullable()->constrained('acct_vouchers')->nullOnDelete();
                $table->string('status', 20)->default('paid');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_tds_challans')) {
            Schema::create('acct_tds_challans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->string('return_period', 7);
                $table->string('section', 20)->nullable();
                $table->string('challan_number', 40)->nullable();
                $table->string('bsr_code', 20)->nullable();
                $table->date('deposit_date');
                $table->decimal('amount', 18, 2)->default(0);
                $table->decimal('interest', 18, 2)->default(0);
                $table->string('status', 20)->default('deposited');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_tds_certificates')) {
            Schema::create('acct_tds_certificates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('party_ledger_id')->constrained('acct_ledgers')->cascadeOnDelete();
                $table->string('certificate_number', 40);
                $table->string('form_type', 20)->default('16A');
                $table->string('financial_year', 9);
                $table->string('quarter', 2)->nullable();
                $table->decimal('tds_amount', 18, 2)->default(0);
                $table->date('issue_date')->nullable();
                $table->string('status', 20)->default('draft');
                $table->timestamps();
                $table->unique(['company_id', 'certificate_number'], 'acct_tds_cert_uq');
            });
        }

        if (! Schema::hasTable('acct_statutory_due_dates')) {
            Schema::create('acct_statutory_due_dates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->string('obligation', 120);
                $table->string('category', 40)->default('gst'); // gst|tds|income_tax|other
                $table->date('due_date');
                $table->string('period_label', 40)->nullable();
                $table->string('status', 20)->default('pending');
                $table->boolean('alert_sent')->default(false);
                $table->timestamps();
                $table->index(['company_id', 'due_date', 'status'], 'acct_stat_due_idx');
            });
        }

        Schema::table('acct_bank_statement_lines', function (Blueprint $table) {
            if (Schema::hasTable('acct_bank_statement_lines') && ! Schema::hasColumn('acct_bank_statement_lines', 'categorized_ledger_id')) {
                $table->foreignId('categorized_ledger_id')->nullable()->constrained('acct_ledgers')->nullOnDelete();
            }
            if (Schema::hasTable('acct_bank_statement_lines') && ! Schema::hasColumn('acct_bank_statement_lines', 'investigation_note')) {
                $table->string('investigation_note', 500)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('acct_bank_statement_lines')) {
            Schema::table('acct_bank_statement_lines', function (Blueprint $table) {
                if (Schema::hasColumn('acct_bank_statement_lines', 'categorized_ledger_id')) {
                    $table->dropConstrainedForeignId('categorized_ledger_id');
                }
                if (Schema::hasColumn('acct_bank_statement_lines', 'investigation_note')) {
                    $table->dropColumn('investigation_note');
                }
            });
        }

        foreach ([
            'acct_statutory_due_dates',
            'acct_tds_certificates',
            'acct_tds_challans',
            'acct_gst_challans',
            'acct_itc_entries',
            'acct_gstr2b_rows',
            'acct_bank_signatory_limits',
            'acct_cash_forecasts',
            'acct_electronic_payments',
            'acct_bank_categorization_rules',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
