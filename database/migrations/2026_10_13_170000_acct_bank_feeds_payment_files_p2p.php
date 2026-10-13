<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('acct_integration_endpoints')) {
            Schema::table('acct_integration_endpoints', function (Blueprint $table) {
                if (! Schema::hasColumn('acct_integration_endpoints', 'bank_account_id')) {
                    $table->foreignId('bank_account_id')->nullable()->after('url')
                        ->constrained('acct_bank_accounts')->nullOnDelete();
                }
                if (! Schema::hasColumn('acct_integration_endpoints', 'auth_header')) {
                    $table->string('auth_header', 500)->nullable()->after('bank_account_id');
                }
                if (! Schema::hasColumn('acct_integration_endpoints', 'config')) {
                    $table->json('config')->nullable()->after('auth_header');
                }
            });
        }

        if (Schema::hasTable('acct_electronic_payments')) {
            Schema::table('acct_electronic_payments', function (Blueprint $table) {
                if (! Schema::hasColumn('acct_electronic_payments', 'beneficiary_account')) {
                    $table->string('beneficiary_account', 40)->nullable()->after('beneficiary');
                }
                if (! Schema::hasColumn('acct_electronic_payments', 'beneficiary_ifsc')) {
                    $table->string('beneficiary_ifsc', 20)->nullable()->after('beneficiary_account');
                }
                if (! Schema::hasColumn('acct_electronic_payments', 'payment_batch_id')) {
                    $table->foreignId('payment_batch_id')->nullable()->after('voucher_id')
                        ->constrained('acct_payment_batches')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('acct_invoices') && ! Schema::hasColumn('acct_invoices', 'goods_receipt_id')) {
            Schema::table('acct_invoices', function (Blueprint $table) {
                $table->foreignId('goods_receipt_id')->nullable()->after('sales_order_id')
                    ->constrained('acct_goods_receipts')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('acct_invoices') && Schema::hasColumn('acct_invoices', 'goods_receipt_id')) {
            Schema::table('acct_invoices', function (Blueprint $table) {
                $table->dropConstrainedForeignId('goods_receipt_id');
            });
        }

        if (Schema::hasTable('acct_electronic_payments')) {
            Schema::table('acct_electronic_payments', function (Blueprint $table) {
                if (Schema::hasColumn('acct_electronic_payments', 'payment_batch_id')) {
                    $table->dropConstrainedForeignId('payment_batch_id');
                }
                if (Schema::hasColumn('acct_electronic_payments', 'beneficiary_ifsc')) {
                    $table->dropColumn('beneficiary_ifsc');
                }
                if (Schema::hasColumn('acct_electronic_payments', 'beneficiary_account')) {
                    $table->dropColumn('beneficiary_account');
                }
            });
        }

        if (Schema::hasTable('acct_integration_endpoints')) {
            Schema::table('acct_integration_endpoints', function (Blueprint $table) {
                if (Schema::hasColumn('acct_integration_endpoints', 'config')) {
                    $table->dropColumn('config');
                }
                if (Schema::hasColumn('acct_integration_endpoints', 'auth_header')) {
                    $table->dropColumn('auth_header');
                }
                if (Schema::hasColumn('acct_integration_endpoints', 'bank_account_id')) {
                    $table->dropConstrainedForeignId('bank_account_id');
                }
            });
        }
    }
};
