<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acct_companies', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_companies', 'accounting_basis')) {
                $table->string('accounting_basis', 20)->default('accrual')->after('books_beginning_from');
            }
            if (! Schema::hasColumn('acct_companies', 'retained_earnings_ledger_id')) {
                $table->foreignId('retained_earnings_ledger_id')->nullable()->after('accounting_basis')
                    ->constrained('acct_ledgers')->nullOnDelete();
            }
        });

        Schema::table('acct_financial_years', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_financial_years', 'is_closed')) {
                $table->boolean('is_closed')->default(false)->after('is_active');
            }
            if (! Schema::hasColumn('acct_financial_years', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('is_closed');
            }
            if (! Schema::hasColumn('acct_financial_years', 'closed_by')) {
                $table->foreignId('closed_by')->nullable()->after('closed_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_financial_years', 'closing_voucher_id')) {
                $table->foreignId('closing_voucher_id')->nullable()->after('closed_by')
                    ->constrained('acct_vouchers')->nullOnDelete();
            }
        });

        if (! Schema::hasTable('acct_accounting_periods')) {
            Schema::create('acct_accounting_periods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('financial_year_id')->constrained('acct_financial_years')->cascadeOnDelete();
                $table->string('name', 60);
                $table->date('start_date');
                $table->date('end_date');
                $table->timestamp('locked_at')->nullable();
                $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['company_id', 'financial_year_id', 'start_date'], 'acct_periods_unique_start');
                $table->index(['company_id', 'start_date', 'end_date']);
            });
        }

        if (! Schema::hasTable('acct_business_units')) {
            Schema::create('acct_business_units', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->string('code', 30)->nullable();
                $table->string('name', 120);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['company_id', 'name']);
            });
        }

        Schema::table('acct_voucher_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_voucher_entries', 'business_unit_id')) {
                $table->foreignId('business_unit_id')->nullable()->after('cost_centre_id')
                    ->constrained('acct_business_units')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_voucher_entries', 'base_debit')) {
                $table->decimal('base_debit', 15, 2)->nullable()->after('credit');
            }
            if (! Schema::hasColumn('acct_voucher_entries', 'base_credit')) {
                $table->decimal('base_credit', 15, 2)->nullable()->after('base_debit');
            }
        });

        Schema::table('acct_vouchers', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_vouchers', 'corrected_voucher_id')) {
                $table->foreignId('corrected_voucher_id')->nullable()->after('reversed_voucher_id')
                    ->constrained('acct_vouchers')->nullOnDelete();
            }
            if (! Schema::hasColumn('acct_vouchers', 'intercompany_pair_id')) {
                $table->uuid('intercompany_pair_id')->nullable()->after('corrected_voucher_id');
                $table->index('intercompany_pair_id');
            }
            if (! Schema::hasColumn('acct_vouchers', 'counterparty_company_id')) {
                $table->foreignId('counterparty_company_id')->nullable()->after('intercompany_pair_id')
                    ->constrained('acct_companies')->nullOnDelete();
            }
        });

        Schema::table('acct_ledgers', function (Blueprint $table) {
            if (! Schema::hasColumn('acct_ledgers', 'is_intercompany')) {
                $table->boolean('is_intercompany')->default(false)->after('is_active');
            }
            if (! Schema::hasColumn('acct_ledgers', 'intercompany_company_id')) {
                $table->foreignId('intercompany_company_id')->nullable()->after('is_intercompany')
                    ->constrained('acct_companies')->nullOnDelete();
            }
        });

        if (! Schema::hasTable('acct_recurring_vouchers')) {
            Schema::create('acct_recurring_vouchers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained('acct_branches')->restrictOnDelete();
                $table->string('name', 120);
                $table->string('voucher_type', 20)->default('journal');
                $table->string('frequency', 20)->default('monthly');
                $table->unsignedTinyInteger('day_of_month')->default(1);
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->date('next_run_on');
                $table->string('narration', 1000)->nullable();
                $table->boolean('auto_post')->default(false);
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['company_id', 'next_run_on', 'is_active']);
            });
        }

        if (! Schema::hasTable('acct_recurring_voucher_lines')) {
            Schema::create('acct_recurring_voucher_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('recurring_voucher_id')->constrained('acct_recurring_vouchers')->cascadeOnDelete();
                $table->foreignId('ledger_id')->constrained('acct_ledgers')->restrictOnDelete();
                $table->unsignedSmallInteger('line_number');
                $table->decimal('debit', 15, 2)->default(0);
                $table->decimal('credit', 15, 2)->default(0);
                $table->foreignId('cost_centre_id')->nullable()->constrained('acct_cost_centres')->nullOnDelete();
                $table->foreignId('business_unit_id')->nullable()->constrained('acct_business_units')->nullOnDelete();
                $table->string('narration', 500)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('acct_recurring_voucher_lines');
        Schema::dropIfExists('acct_recurring_vouchers');

        Schema::table('acct_ledgers', function (Blueprint $table) {
            if (Schema::hasColumn('acct_ledgers', 'intercompany_company_id')) {
                $table->dropConstrainedForeignId('intercompany_company_id');
            }
            if (Schema::hasColumn('acct_ledgers', 'is_intercompany')) {
                $table->dropColumn('is_intercompany');
            }
        });

        Schema::table('acct_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('acct_vouchers', 'counterparty_company_id')) {
                $table->dropConstrainedForeignId('counterparty_company_id');
            }
            if (Schema::hasColumn('acct_vouchers', 'intercompany_pair_id')) {
                $table->dropColumn('intercompany_pair_id');
            }
            if (Schema::hasColumn('acct_vouchers', 'corrected_voucher_id')) {
                $table->dropConstrainedForeignId('corrected_voucher_id');
            }
        });

        Schema::table('acct_voucher_entries', function (Blueprint $table) {
            if (Schema::hasColumn('acct_voucher_entries', 'business_unit_id')) {
                $table->dropConstrainedForeignId('business_unit_id');
            }
            if (Schema::hasColumn('acct_voucher_entries', 'base_credit')) {
                $table->dropColumn('base_credit');
            }
            if (Schema::hasColumn('acct_voucher_entries', 'base_debit')) {
                $table->dropColumn('base_debit');
            }
        });

        Schema::dropIfExists('acct_business_units');
        Schema::dropIfExists('acct_accounting_periods');

        Schema::table('acct_financial_years', function (Blueprint $table) {
            if (Schema::hasColumn('acct_financial_years', 'closing_voucher_id')) {
                $table->dropConstrainedForeignId('closing_voucher_id');
            }
            if (Schema::hasColumn('acct_financial_years', 'closed_by')) {
                $table->dropConstrainedForeignId('closed_by');
            }
            foreach (['closed_at', 'is_closed'] as $col) {
                if (Schema::hasColumn('acct_financial_years', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('acct_companies', function (Blueprint $table) {
            if (Schema::hasColumn('acct_companies', 'retained_earnings_ledger_id')) {
                $table->dropConstrainedForeignId('retained_earnings_ledger_id');
            }
            if (Schema::hasColumn('acct_companies', 'accounting_basis')) {
                $table->dropColumn('accounting_basis');
            }
        });
    }
};
