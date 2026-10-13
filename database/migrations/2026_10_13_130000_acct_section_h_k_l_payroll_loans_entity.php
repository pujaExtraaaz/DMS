<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('acct_salary_structures')) {
            Schema::create('acct_salary_structures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->string('name', 120);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['company_id', 'name']);
            });
        }

        if (! Schema::hasTable('acct_salary_structure_lines')) {
            Schema::create('acct_salary_structure_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('salary_structure_id')->constrained('acct_salary_structures')->cascadeOnDelete();
                $table->foreignId('pay_head_id')->constrained('acct_pay_heads')->cascadeOnDelete();
                $table->decimal('amount', 18, 2)->default(0);
                $table->decimal('percent_of_basic', 8, 2)->nullable();
                $table->boolean('is_earning')->default(true);
                $table->timestamps();
            });
        }

        Schema::table('acct_employees', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_employees', 'salary_structure_id')) {
                $table->foreignId('salary_structure_id')->nullable()->constrained('acct_salary_structures')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_employees', 'basic_salary')) {
                $table->decimal('basic_salary', 18, 2)->nullable();
            }
            if (! Schema::hasColumn('acct_employees', 'pan')) {
                $table->string('pan', 10)->nullable();
            }
            if (! Schema::hasColumn('acct_employees', 'uan')) {
                $table->string('uan', 20)->nullable();
            }
            if (! Schema::hasColumn('acct_employees', 'esi_number')) {
                $table->string('esi_number', 30)->nullable();
            }
        });

        if (! Schema::hasTable('acct_employee_loans')) {
            Schema::create('acct_employee_loans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('acct_employees')->cascadeOnDelete();
                $table->date('loan_date');
                $table->decimal('principal', 18, 2)->default(0);
                $table->decimal('interest_rate', 8, 4)->default(0);
                $table->unsignedSmallInteger('tenure_months')->default(12);
                $table->decimal('emi_amount', 18, 2)->default(0);
                $table->decimal('outstanding', 18, 2)->default(0);
                $table->string('status', 20)->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_payroll_provisions')) {
            Schema::create('acct_payroll_provisions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->string('kind', 40); // gratuity|leave|bonus|encashment
                $table->string('period', 7)->nullable();
                $table->decimal('amount', 18, 2)->default(0);
                $table->foreignId('expense_ledger_id')->nullable()->constrained('acct_ledgers')->nullOnDelete();
                $table->foreignId('liability_ledger_id')->nullable()->constrained('acct_ledgers')->nullOnDelete();
                $table->foreignId('voucher_id')->nullable()->constrained('acct_vouchers')->nullOnDelete();
                $table->string('status', 20)->default('open');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_loans')) {
            Schema::create('acct_loans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->string('loan_number', 40);
                $table->string('lender_name', 160);
                $table->string('loan_type', 40)->default('term'); // term|od|overdraft|lease
                $table->boolean('is_long_term')->default(true);
                $table->date('disbursement_date');
                $table->decimal('principal', 18, 2)->default(0);
                $table->decimal('interest_rate', 8, 4)->default(0);
                $table->unsignedSmallInteger('tenure_months')->default(12);
                $table->decimal('emi_amount', 18, 2)->default(0);
                $table->decimal('outstanding_principal', 18, 2)->default(0);
                $table->foreignId('loan_ledger_id')->nullable()->constrained('acct_ledgers')->nullOnDelete();
                $table->foreignId('interest_ledger_id')->nullable()->constrained('acct_ledgers')->nullOnDelete();
                $table->foreignId('bank_account_id')->nullable()->constrained('acct_bank_accounts')->nullOnDelete();
                $table->decimal('overdraft_limit', 18, 2)->nullable();
                $table->string('status', 20)->default('active');
                $table->timestamps();
                $table->unique(['company_id', 'loan_number']);
            });
        }

        if (! Schema::hasTable('acct_loan_schedules')) {
            Schema::create('acct_loan_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('loan_id')->constrained('acct_loans')->cascadeOnDelete();
                $table->unsignedSmallInteger('installment_no');
                $table->date('due_date');
                $table->decimal('principal_component', 18, 2)->default(0);
                $table->decimal('interest_component', 18, 2)->default(0);
                $table->decimal('emi_amount', 18, 2)->default(0);
                $table->decimal('outstanding_after', 18, 2)->default(0);
                $table->boolean('is_paid')->default(false);
                $table->date('paid_date')->nullable();
                $table->foreignId('payment_voucher_id')->nullable()->constrained('acct_vouchers')->nullOnDelete();
                $table->timestamps();
                $table->unique(['loan_id', 'installment_no']);
            });
        }

        if (! Schema::hasTable('acct_contingent_liabilities')) {
            Schema::create('acct_contingent_liabilities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->string('kind', 40)->default('guarantee'); // guarantee|contingent|provision
                $table->string('description', 255);
                $table->decimal('amount', 18, 2)->default(0);
                $table->date('as_of_date');
                $table->string('counterparty', 160)->nullable();
                $table->string('status', 20)->default('open');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_equity_transactions')) {
            Schema::create('acct_equity_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->string('kind', 40); // capital|drawing|dividend|reserve
                $table->date('txn_date');
                $table->decimal('amount', 18, 2)->default(0);
                $table->foreignId('ledger_id')->nullable()->constrained('acct_ledgers')->nullOnDelete();
                $table->foreignId('voucher_id')->nullable()->constrained('acct_vouchers')->nullOnDelete();
                $table->string('narration', 500)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_related_parties')) {
            Schema::create('acct_related_parties', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('party_ledger_id')->constrained('acct_ledgers')->cascadeOnDelete();
                $table->string('relationship', 80);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['company_id', 'party_ledger_id']);
            });
        }

        if (! Schema::hasTable('acct_consolidation_maps')) {
            Schema::create('acct_consolidation_maps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('subsidiary_company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->decimal('ownership_percent', 8, 4)->default(100);
                $table->string('reporting_currency', 3)->default('INR');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['parent_company_id', 'subsidiary_company_id'], 'acct_consol_map_uq');
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'acct_consolidation_maps',
            'acct_related_parties',
            'acct_equity_transactions',
            'acct_contingent_liabilities',
            'acct_loan_schedules',
            'acct_loans',
            'acct_payroll_provisions',
            'acct_employee_loans',
            'acct_salary_structure_lines',
            'acct_salary_structures',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('acct_employees', function (Blueprint $table) {
            if (Schema::hasColumn('acct_employees', 'salary_structure_id')) {
                $table->dropConstrainedForeignId('salary_structure_id');
            }
            foreach (['basic_salary', 'pan', 'uan', 'esi_number'] as $col) {
                if (Schema::hasColumn('acct_employees', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
