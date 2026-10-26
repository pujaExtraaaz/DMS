<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('invoices') && ! Schema::hasColumn('invoices', 'company_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('customer_id')
                    ->constrained('companies')
                    ->nullOnDelete();
            });

            // Backfill company_id from linked orders/warehouses or customers where possible
            try {
                if (Schema::hasTable('orders') && Schema::hasTable('warehouses')) {
                    $orderWarehouses = DB::table('orders')
                        ->join('warehouses', 'orders.warehouse_id', '=', 'warehouses.id')
                        ->whereNotNull('warehouses.company_id')
                        ->pluck('warehouses.company_id', 'orders.id');

                    foreach ($orderWarehouses as $orderId => $companyId) {
                        DB::table('invoices')
                            ->where('order_id', $orderId)
                            ->whereNull('company_id')
                            ->update(['company_id' => $companyId]);
                    }
                }

                if (Schema::hasTable('customers')) {
                    $customerCompanies = DB::table('customers')
                        ->whereNotNull('company_id')
                        ->pluck('company_id', 'id');

                    foreach ($customerCompanies as $customerId => $companyId) {
                        DB::table('invoices')
                            ->where('customer_id', $customerId)
                            ->whereNull('company_id')
                            ->update(['company_id' => $companyId]);
                    }
                }

                $defaultCompanyId = DB::table('companies')->where('is_default', true)->value('id')
                    ?? DB::table('companies')->orderBy('id')->value('id');

                if ($defaultCompanyId) {
                    DB::table('invoices')->whereNull('company_id')->update(['company_id' => $defaultCompanyId]);
                }
            } catch (\Throwable $e) {
                // Safe migration fallback
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'company_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }
    }
};

