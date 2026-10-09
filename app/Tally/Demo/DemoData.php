<?php

namespace Tally\Demo;

use Tally\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class DemoData
{
    public const GSTIN = '03AABCU9603R1ZM';

    public function assertAllowed(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Demo data cannot be seeded or removed in production.');
        }
    }

    public function forget(): void
    {
        $this->assertAllowed();
        $company = Company::query()->where('gstin', self::GSTIN)->first();

        if (! $company) {
            return;
        }

        DB::transaction(function () use ($company) {
            $id = $company->id;
            $voucherIds = DB::table('acct_vouchers')->where('company_id', $id)->pluck('id');
            $invoiceIds = DB::table('acct_invoices')->where('company_id', $id)->pluck('id');
            $stockIds = DB::table('acct_stock_transactions')->where('company_id', $id)->pluck('id');

            DB::table('acct_bank_reconciliations')->where('company_id', $id)->delete();
            DB::table('acct_bill_allocations')->where('company_id', $id)->delete();
            DB::table('acct_bills')->where('company_id', $id)->delete();
            DB::table('acct_stock_movements')->where('company_id', $id)->delete();
            DB::table('acct_manufacturing_orders')->where('company_id', $id)->delete();
            $bomIds = DB::table('acct_bills_of_materials')->where('company_id', $id)->pluck('id');
            DB::table('acct_bom_byproducts')->whereIn('bill_of_material_id', $bomIds)->delete();
            DB::table('acct_bom_lines')->whereIn('bill_of_material_id', $bomIds)->delete();
            DB::table('acct_bills_of_materials')->where('company_id', $id)->delete();
            DB::table('acct_stock_serials')->where('company_id', $id)->delete();
            DB::table('acct_stock_batches')->where('company_id', $id)->delete();
            DB::table('acct_stock_transaction_lines')->whereIn('stock_transaction_id', $stockIds)->delete();
            DB::table('acct_stock_transactions')->where('company_id', $id)->delete();
            if (Schema::hasTable('acct_purchase_orders')) {
                $orderIds = DB::table('acct_purchase_orders')->where('company_id', $id)->pluck('id');
                DB::table('acct_purchase_order_lines')->whereIn('purchase_order_id', $orderIds)->delete();
                DB::table('acct_purchase_orders')->where('company_id', $id)->delete();
            }
            DB::table('acct_invoice_lines')->whereIn('invoice_id', $invoiceIds)->delete();
            DB::table('acct_invoices')->where('company_id', $id)->delete();
            DB::table('acct_integration_references')->where('company_id', $id)->delete();
            DB::table('acct_voucher_entries')->whereIn('voucher_id', $voucherIds)->delete();
            DB::table('acct_vouchers')->where('company_id', $id)->delete();
            DB::table('acct_voucher_sequences')->where('company_id', $id)->delete();
            DB::table('acct_budgets')->where('company_id', $id)->delete();
            DB::table('acct_ledgers')->where('company_id', $id)->update(['deduction_section_id' => null]);
            DB::table('acct_tax_accounts')->where('company_id', $id)->delete();
            DB::table('acct_products')->where('company_id', $id)->update(['hsn_sac_id' => null, 'tax_rate_id' => null]);
            DB::table('acct_products')->where('company_id', $id)->delete();
            DB::table('acct_hsn_sacs')->where('company_id', $id)->delete();
            DB::table('acct_tax_rates')->where('company_id', $id)->delete();
            DB::table('acct_tax_categories')->where('company_id', $id)->delete();
            DB::table('acct_deduction_sections')->where('company_id', $id)->delete();
            DB::table('acct_cost_centres')->where('company_id', $id)->delete();
            DB::table('acct_cost_categories')->where('company_id', $id)->delete();
            DB::table('acct_godowns')->where('company_id', $id)->delete();
            DB::table('acct_product_groups')->where('company_id', $id)->delete();
            DB::table('acct_units')->where('company_id', $id)->delete();
            DB::table('acct_bank_accounts')->where('company_id', $id)->delete();
            if (Schema::hasTable('acct_parties')) {
                DB::table('acct_parties')->where('company_id', $id)->delete();
            }
            if (Schema::hasTable('acct_product_barcodes')) {
                DB::table('acct_product_barcodes')->where('company_id', $id)->delete();
            }
            if (Schema::hasTable('acct_integrations')) {
                $integrationIds = DB::table('acct_integrations')->where('company_id', $id)->pluck('id');
                DB::table('acct_integration_syncs')->whereIn('integration_id', $integrationIds)->delete();
                DB::table('acct_integrations')->where('company_id', $id)->delete();
            }
            if (Schema::hasTable('acct_preferences')) {
                DB::table('acct_preferences')->where('company_id', $id)->delete();
            }
            DB::table('acct_ledgers')->where('company_id', $id)->delete();
            DB::table('acct_account_groups')->where('company_id', $id)->update(['parent_id' => null]);
            DB::table('acct_account_groups')->where('company_id', $id)->delete();
            if (Schema::hasTable('acct_audit_logs')) {
                DB::table('acct_audit_logs')->where('company_id', $id)->delete();
            }
            DB::table('acct_branches')->where('company_id', $id)->delete();
            DB::table('acct_financial_years')->where('company_id', $id)->delete();
            DB::table('acct_companies')->where('id', $id)->delete();
        });
    }
}
