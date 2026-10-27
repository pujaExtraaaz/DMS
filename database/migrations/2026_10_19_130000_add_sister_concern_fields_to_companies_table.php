<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'company_type')) {
                $table->string('company_type', 50)->nullable()->after('legal_name');
            }
            if (! Schema::hasColumn('companies', 'incorporation_no')) {
                $table->string('incorporation_no', 50)->nullable()->after('company_type');
            }
            if (! Schema::hasColumn('companies', 'incorporation_date')) {
                $table->date('incorporation_date')->nullable()->after('incorporation_no');
            }
            if (! Schema::hasColumn('companies', 'logo_emoji')) {
                $table->string('logo_emoji', 20)->nullable()->default('🏢')->after('incorporation_date');
            }
            if (! Schema::hasColumn('companies', 'iec_code')) {
                $table->string('iec_code', 30)->nullable()->after('msme_registration_no');
            }
            if (! Schema::hasColumn('companies', 'city')) {
                $table->string('city', 100)->nullable()->after('address');
            }
            if (! Schema::hasColumn('companies', 'country')) {
                $table->string('country', 100)->nullable()->default('India')->after('state');
            }
            if (! Schema::hasColumn('companies', 'nature_of_business')) {
                $table->string('nature_of_business', 255)->nullable()->after('website');
            }
            if (! Schema::hasColumn('companies', 'authorized_capital')) {
                $table->decimal('authorized_capital', 15, 2)->nullable()->after('nature_of_business');
            }
            if (! Schema::hasColumn('companies', 'paid_up_capital')) {
                $table->decimal('paid_up_capital', 15, 2)->nullable()->after('authorized_capital');
            }
            if (! Schema::hasColumn('companies', 'annual_turnover')) {
                $table->decimal('annual_turnover', 15, 2)->nullable()->after('paid_up_capital');
            }
            if (! Schema::hasColumn('companies', 'financial_year')) {
                $table->string('financial_year', 30)->nullable()->after('annual_turnover');
            }
            if (! Schema::hasColumn('companies', 'accounting_software')) {
                $table->string('accounting_software', 50)->nullable()->after('financial_year');
            }
            if (! Schema::hasColumn('companies', 'branches_count_manual')) {
                $table->unsignedInteger('branches_count_manual')->nullable()->default(0)->after('accounting_software');
            }
            if (! Schema::hasColumn('companies', 'employees_count_manual')) {
                $table->unsignedInteger('employees_count_manual')->nullable()->default(0)->after('branches_count_manual');
            }
            if (! Schema::hasColumn('companies', 'relationship_type')) {
                $table->string('relationship_type', 50)->nullable()->default('Sister Concern')->after('employees_count_manual');
            }
            if (! Schema::hasColumn('companies', 'shareholding_percentage')) {
                $table->decimal('shareholding_percentage', 5, 2)->nullable()->default(0.00)->after('relationship_type');
            }
            if (! Schema::hasColumn('companies', 'compliance_status')) {
                $table->string('compliance_status', 50)->nullable()->default('Compliant')->after('shareholding_percentage');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $cols = [
                'company_type',
                'incorporation_no',
                'incorporation_date',
                'logo_emoji',
                'iec_code',
                'city',
                'country',
                'nature_of_business',
                'authorized_capital',
                'paid_up_capital',
                'annual_turnover',
                'financial_year',
                'accounting_software',
                'branches_count_manual',
                'employees_count_manual',
                'relationship_type',
                'shareholding_percentage',
                'compliance_status',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('companies', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

