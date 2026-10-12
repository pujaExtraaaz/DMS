<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('acct_departments')) {
            Schema::create('acct_departments', function (Blueprint $table) {
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
            if (! Schema::hasColumn('acct_voucher_entries', 'department_id')) {
                $table->foreignId('department_id')->nullable()->after('business_unit_id')
                    ->constrained('acct_departments')->nullOnDelete();
            }
        });

        if (Schema::hasTable('acct_recurring_voucher_lines')
            && ! Schema::hasColumn('acct_recurring_voucher_lines', 'department_id')) {
            Schema::table('acct_recurring_voucher_lines', function (Blueprint $table) {
                $table->foreignId('department_id')->nullable()->constrained('acct_departments')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('acct_recurring_voucher_lines')
            && Schema::hasColumn('acct_recurring_voucher_lines', 'department_id')) {
            Schema::table('acct_recurring_voucher_lines', function (Blueprint $table) {
                $table->dropConstrainedForeignId('department_id');
            });
        }

        if (Schema::hasColumn('acct_voucher_entries', 'department_id')) {
            Schema::table('acct_voucher_entries', function (Blueprint $table) {
                $table->dropConstrainedForeignId('department_id');
            });
        }

        Schema::dropIfExists('acct_departments');
    }
};
