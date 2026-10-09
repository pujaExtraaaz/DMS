<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acct_vouchers', function (Blueprint $table) {
            $table->index(['company_id', 'financial_year_id', 'status', 'voucher_date'], 'vouchers_report_index');
        });

        Schema::table('acct_invoices', function (Blueprint $table) {
            $table->index(['company_id', 'status', 'invoice_date'], 'invoices_report_index');
        });

        Schema::table('acct_stock_movements', function (Blueprint $table) {
            $table->index(
                ['company_id', 'financial_year_id', 'product_id', 'movement_date'],
                'stock_movements_report_index',
            );
        });

        Schema::table('acct_stock_batches', function (Blueprint $table) {
            $table->index(['company_id', 'expires_on'], 'stock_batches_expiry_index');
        });

        Schema::table('acct_manufacturing_orders', function (Blueprint $table) {
            $table->index(
                ['company_id', 'financial_year_id', 'manufactured_on'],
                'manufacturing_orders_report_index',
            );
        });

        Schema::table('acct_audit_logs', function (Blueprint $table) {
            $table->index(['action', 'created_at'], 'audit_logs_action_index');
        });
    }

    public function down(): void
    {
        Schema::table('acct_vouchers', function (Blueprint $table) {
            $table->dropIndex('vouchers_report_index');
        });

        Schema::table('acct_invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_report_index');
        });

        Schema::table('acct_stock_movements', function (Blueprint $table) {
            $table->dropIndex('stock_movements_report_index');
        });

        Schema::table('acct_stock_batches', function (Blueprint $table) {
            $table->dropIndex('stock_batches_expiry_index');
        });

        Schema::table('acct_manufacturing_orders', function (Blueprint $table) {
            $table->dropIndex('manufacturing_orders_report_index');
        });

        Schema::table('acct_audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_action_index');
        });
    }
};
