<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acct_companies', function (Blueprint $table) {
            $table->string('tax_pricing', 12)->default('exclusive')->after('gst_registration_type');
            $table->string('tax_rounding', 12)->default('paisa')->after('tax_pricing');
        });

        Schema::create('acct_cost_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->string('name');
            $table->string('code', 32)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'name']);
            $table->unique(['company_id', 'code']);
        });

        Schema::create('acct_cost_centres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->foreignId('cost_category_id')->nullable()->constrained('acct_cost_categories')->restrictOnDelete();
            $table->string('name');
            $table->string('code', 32)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'name']);
            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'cost_category_id']);
        });

        Schema::table('acct_voucher_entries', function (Blueprint $table) {
            $table->foreignId('cost_centre_id')->nullable()->after('ledger_id')->constrained('acct_cost_centres')->nullOnDelete();
        });

        Schema::create('acct_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->foreignId('financial_year_id')->constrained('acct_financial_years')->restrictOnDelete();
            $table->string('name');
            $table->date('period_start');
            $table->date('period_end');
            $table->foreignId('ledger_id')->nullable()->constrained('acct_ledgers')->restrictOnDelete();
            $table->foreignId('account_group_id')->nullable()->constrained('acct_account_groups')->restrictOnDelete();
            $table->foreignId('cost_centre_id')->nullable()->constrained('acct_cost_centres')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->index(['company_id', 'financial_year_id', 'period_start'], 'budgets_period_index');
        });

        Schema::create('acct_deduction_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->restrictOnDelete();
            $table->string('kind', 8);
            $table->string('name');
            $table->string('section_code', 20);
            $table->decimal('rate', 8, 4);
            $table->decimal('threshold_amount', 15, 2)->default(0);
            $table->string('party_role', 12)->default('any');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'section_code']);
            $table->index(['company_id', 'kind', 'is_active']);
        });

        Schema::table('acct_ledgers', function (Blueprint $table) {
            $table->foreignId('deduction_section_id')->nullable()->after('credit_days')->constrained('acct_deduction_sections')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('acct_ledgers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deduction_section_id');
        });

        Schema::dropIfExists('acct_deduction_sections');
        Schema::dropIfExists('acct_budgets');

        Schema::table('acct_voucher_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cost_centre_id');
        });

        Schema::dropIfExists('acct_cost_centres');
        Schema::dropIfExists('acct_cost_categories');

        Schema::table('acct_companies', function (Blueprint $table) {
            $table->dropColumn(['tax_pricing', 'tax_rounding']);
        });
    }
};
