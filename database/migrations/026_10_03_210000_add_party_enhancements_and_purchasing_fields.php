<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Party Bank Accounts table (multi-bank accounts per party)
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

        // 2. Party Credit Cheques table (PDC / Credit cheques)
        if (! Schema::hasTable('party_credit_cheques')) {
            Schema::create('party_credit_cheques', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->string('cheque_number', 50);
                $table->string('bank_name')->nullable();
                $table->string('branch_name')->nullable();
                $table->date('cheque_date')->nullable();
                $table->decimal('amount', 14, 2)->default(0);
                $table->string('cheque_type', 30)->default('regular'); // regular, security, pdc
                $table->string('status', 30)->default('pending'); // pending, received, deposited, cleared, cancelled, bounced
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 3. Customers table enhancements (Sales Manager)
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'sales_manager_id')) {
                $table->foreignId('sales_manager_id')->nullable()->after('salesperson_id')->constrained('users')->nullOnDelete();
            }
        });

        // 4. Purchase Order Items table enhancements (Batch Name)
        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_order_items', 'batch_no')) {
                $table->string('batch_no', 60)->nullable()->after('weight');
            }
        });

        // 5. Purchase Invoices table enhancements (Credit Days)
        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_invoices', 'credit_days')) {
                $table->unsignedInteger('credit_days')->nullable()->after('due_date');
            }
        });

        // 6. Companies table enhancements (Logo)
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('due_date_basis');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'logo_path')) {
                $table->dropColumn('logo_path');
            }
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_invoices', 'credit_days')) {
                $table->dropColumn('credit_days');
            }
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_order_items', 'batch_no')) {
                $table->dropColumn('batch_no');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'sales_manager_id')) {
                $table->dropForeign(['sales_manager_id']);
                $table->dropColumn('sales_manager_id');
            }
        });

        Schema::dropIfExists('party_credit_cheques');
        Schema::dropIfExists('party_bank_accounts');
    }
};