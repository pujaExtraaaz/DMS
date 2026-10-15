<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Truncates operational / transactional business data for a clean Avit tenant seed.
 * PRESERVES: users, roles, permissions, model_has_*, password_reset tokens,
 * companies / branches / warehouses / financial_years structure, and Books chart masters
 * that belong to the linked Avit company (ledgers may remain; vouchers cleared).
 */
class CleanBusinessDataSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ($this->tables() as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            try {
                DB::table($table)->truncate();
                $this->command?->line("truncated {$table}");
            } catch (\Throwable $e) {
                $this->command?->warn("skip {$table}: ".$e->getMessage());
            }
        }

        Schema::enableForeignKeyConstraints();
        $this->command?->info('Business transactional data cleaned. Users and Avit company profile kept.');
    }

    /**
     * @return list<string>
     */
    protected function tables(): array
    {
        return [
            // DMS operational
            'invoice_items',
            'invoices',
            'order_items',
            'orders',
            'payment_allocations',
            'payments',
            'credit_note_items',
            'credit_notes',
            'purchase_items',
            'purchases',
            'purchase_invoice_items',
            'purchase_invoices',
            'purchase_order_items',
            'purchase_orders',
            'delivery_items',
            'deliveries',
            'load_sheet_items',
            'load_sheets',
            'stock_movements',
            'stock_levels',
            'stock_adjustments',
            'stock_adjustment_items',
            'quotation_items',
            'quotations',
            'outstanding_ledgers',
            'od_transactions',
            // Catalog demo rows (re-seed later if needed)
            'price_masters',
            'product_uoms',
            'products',
            'sub_categories',
            'categories',
            'brands',
            'customers',
            'customer_types',
            'areas',
            'routes',
            'vehicles',
            'drivers',
            'delivery_people',
            'uoms',
            'tax_rates',
            'cheque_bounces',
            'cheques',
            // Books transactional (keep acct_companies / groups / ledgers)
            'acct_voucher_entries',
            'acct_voucher_lines',
            'acct_vouchers',
            'acct_invoice_lines',
            'acct_invoices',
            'acct_stock_transaction_lines',
            'acct_stock_transactions',
            'acct_sales_order_lines',
            'acct_sales_orders',
            'acct_purchase_order_lines',
            'acct_purchase_orders',
            'acct_payment_batch_lines',
            'acct_payment_batches',
            'acct_bank_statement_lines',
            'acct_bank_statements',
            'acct_audit_logs',
        ];
    }
}
