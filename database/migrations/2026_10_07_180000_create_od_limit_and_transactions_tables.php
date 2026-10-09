<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('od_accounts')) {
            Schema::create('od_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('account_number', 64);
                $table->string('bank_name', 150);
                $table->string('ifsc_code', 30)->nullable();
                $table->decimal('od_limit', 15, 2)->default(0);
                $table->decimal('interest_rate', 6, 2)->default(0);
                $table->string('interest_calculation_method', 50)->default('daily_simple');
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['company_id', 'status']);
                $table->index(['company_id', 'account_number']);
            });
        }

        if (! Schema::hasTable('bank_account_transactions')) {
            Schema::create('bank_account_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('account_number', 64);
                $table->foreignId('od_account_id')->nullable()->constrained('od_accounts')->nullOnDelete();
                $table->date('transaction_date');
                $table->string('transaction_no', 80)->nullable();
                $table->string('description', 255);
                $table->string('transaction_type', 50)->default('debit'); // debit, credit, withdrawal, deposit, payment, receipt, bank_charges, interest, opening_balance
                $table->decimal('debit', 15, 2)->default(0);
                $table->decimal('credit', 15, 2)->default(0);
                $table->nullableMorphs('reference');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['company_id', 'account_number', 'transaction_date'], 'bat_comp_acc_date_idx');
                $table->index(['transaction_date']);
            });
        }

        // Register OD permissions if Spatie tables exist
        try {
            if (Schema::hasTable('permissions')) {
                $permissions = [
                    'od.view',
                    'od.create',
                    'od.edit',
                    'od.delete',
                    'od.manage',
                ];

                foreach ($permissions as $p) {
                    Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
                }

                $roles = Role::whereIn('name', ['super-admin', 'client-admin'])->get();
                foreach ($roles as $role) {
                    $role->givePermissionTo($permissions);
                }
            }
        } catch (\Throwable $e) {
            // Ignore if permissions tables are absent during early bootstrap
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_account_transactions');
        Schema::dropIfExists('od_accounts');
    }
};

