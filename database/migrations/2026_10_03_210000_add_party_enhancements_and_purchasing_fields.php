<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Party Bank Accounts
        if (! Schema::hasTable('party_bank_accounts')) {
            Schema::create('party_bank_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->string('account_holder_name')->nullable();
                $table->string('bank_name')->nullable();
                $table->string('branch_name')->nullable();
                $table->string('account_number')->nullable();
                $table->string('account_type')->default('current'); // current, savings
                $table->string('ifsc_code', 20)->nullable();
                $table->boolean('is_primary')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Party Credit Cheques
        if (! Schema::hasTable('party_credit_cheques')) {
            Schema::create('party_credit_cheques', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->string('cheque_number', 50);
                $table->string('bank_name')->nullable();
                $table->string('branch_name')->nullable();
                $table->date('cheque_date')->nullable();
                $table->decimal('amount', 14, 2)->default(0);
                $table->string('cheque_type')->nullable(); // security, post_dated, regular
                $table->enum('status', ['pending', 'received', 'deposited', 'cleared', 'cancelled', 'bounced'])->default('pending');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 3. Sales Manager on customers
        if (! Schema::hasColumn('customers', 'sales_manager_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->foreignId('sales_manager_id')->nullable()->after('salesperson_id')->constrained('users')->nullOnDelete();
            });
        }

        // 4. Batch Name on purchase_order_items
        if (! Schema::hasColumn('purchase_order_items', 'batch_no')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                $table->string('batch_no', 60)->nullable()->after('line_total');
            });
        }

        // 5. Credit Days on purchase_invoices
        if (! Schema::hasColumn('purchase_invoices', 'credit_days')) {
            Schema::table('purchase_invoices', function (Blueprint $table) {
                $table->unsignedInteger('credit_days')->nullable()->after('due_date');
            });
        }

        // 6. Logo Path on companies
        if (! Schema::hasColumn('companies', 'logo_path')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('logo_path')->nullable()->after('msme_registration_no');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('companies', 'logo_path')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('logo_path');
            });
        }

        if (Schema::hasColumn('purchase_invoices', 'credit_days')) {
            Schema::table('purchase_invoices', function (Blueprint $table) {
                $table->dropColumn('credit_days');
            });
        }

        if (Schema::hasColumn('purchase_order_items', 'batch_no')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                $table->dropColumn('batch_no');
            });
        }

        if (Schema::hasColumn('customers', 'sales_manager_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropForeign(['sales_manager_id']);
                $table->dropColumn('sales_manager_id');
            });
        }

        Schema::dropIfExists('party_credit_cheques');
        Schema::dropIfExists('party_bank_accounts');
    }
};
