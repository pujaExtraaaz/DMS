<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acct_invoices', function (Blueprint $table) {
            $table->foreignId('sales_order_id')->nullable()->after('voucher_id')->constrained('acct_sales_orders')->nullOnDelete();
            $table->foreignId('gst_registration_id')->nullable()->after('sales_order_id')->constrained('acct_gst_registrations')->nullOnDelete();
            $table->boolean('reverse_charge')->default(false)->after('gst_registration_id');
        });

        Schema::table('acct_sales_order_lines', function (Blueprint $table) {
            $table->decimal('fulfilled_quantity', 15, 4)->default(0)->after('quantity');
            $table->decimal('cancelled_quantity', 15, 4)->default(0)->after('fulfilled_quantity');
        });

        Schema::table('acct_vouchers', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('narration')->constrained('acct_currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 15, 6)->nullable()->after('currency_id');
            $table->decimal('foreign_total', 15, 2)->nullable()->after('exchange_rate');
            $table->foreignId('payment_request_id')->nullable()->after('foreign_total')->constrained('acct_payment_requests')->nullOnDelete();
            $table->foreignId('merchant_profile_id')->nullable()->after('payment_request_id')->constrained('acct_merchant_profiles')->nullOnDelete();
        });

        Schema::table('acct_payment_requests', function (Blueprint $table) {
            $table->decimal('paid_amount', 15, 2)->default(0)->after('amount');
        });

        Schema::table('acct_employees', function (Blueprint $table) {
            $table->string('group_name', 80)->nullable()->after('designation');
        });

        Schema::table('acct_pay_heads', function (Blueprint $table) {
            $table->string('calculation', 20)->default('flat')->after('nature');
            $table->decimal('rate_or_amount', 15, 2)->default(0)->after('calculation');
            $table->foreignId('employee_id')->nullable()->after('ledger_id')->constrained('acct_employees')->nullOnDelete();
        });

        Schema::table('acct_bank_statement_lines', function (Blueprint $table) {
            $table->string('transaction_id', 80)->nullable()->after('reference');
        });

        Schema::table('acct_payroll_lines', function (Blueprint $table) {
            $table->decimal('pf_amount', 15, 2)->default(0)->after('deductions');
            $table->decimal('esi_amount', 15, 2)->default(0)->after('pf_amount');
            $table->unsignedSmallInteger('payable_days')->default(0)->after('esi_amount');
        });

        Schema::create('acct_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('acct_companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('acct_employees')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->string('attendance_type', 20);
            $table->timestamps();
            $table->unique(['employee_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::table('acct_bank_statement_lines', function (Blueprint $table) {
            $table->dropColumn('transaction_id');
        });

        Schema::table('acct_pay_heads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_id');
            $table->dropColumn(['calculation', 'rate_or_amount']);
        });

        Schema::dropIfExists('acct_attendances');

        Schema::table('acct_payroll_lines', function (Blueprint $table) {
            $table->dropColumn(['pf_amount', 'esi_amount', 'payable_days']);
        });

        Schema::table('acct_employees', function (Blueprint $table) {
            $table->dropColumn('group_name');
        });

        Schema::table('acct_payment_requests', function (Blueprint $table) {
            $table->dropColumn('paid_amount');
        });

        Schema::table('acct_vouchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('merchant_profile_id');
            $table->dropConstrainedForeignId('payment_request_id');
            $table->dropColumn(['exchange_rate', 'foreign_total']);
            $table->dropConstrainedForeignId('currency_id');
        });

        Schema::table('acct_sales_order_lines', function (Blueprint $table) {
            $table->dropColumn(['fulfilled_quantity', 'cancelled_quantity']);
        });

        Schema::table('acct_invoices', function (Blueprint $table) {
            $table->dropColumn('reverse_charge');
            $table->dropConstrainedForeignId('gst_registration_id');
            $table->dropConstrainedForeignId('sales_order_id');
        });
    }
};
