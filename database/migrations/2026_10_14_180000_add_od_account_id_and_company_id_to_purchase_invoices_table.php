<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_invoices', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('supplier_id')->constrained('companies')->nullOnDelete();
            }
            if (! Schema::hasColumn('purchase_invoices', 'od_account_id')) {
                $table->foreignId('od_account_id')->nullable()->after('warehouse_id')->constrained('od_accounts')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_invoices', 'od_account_id')) {
                $table->dropConstrainedForeignId('od_account_id');
            }
            if (Schema::hasColumn('purchase_invoices', 'company_id')) {
                $table->dropConstrainedForeignId('company_id');
            }
        });
    }
};
