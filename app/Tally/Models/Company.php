<?php

namespace Tally\Models;

use Tally\Models\AccountingModel;

use Tally\Accounting\DefaultAccountGroups;
use Tally\Integration\Concerns\HasExternalReference;
use Tally\Tax\GstRegistrationType;
use Tally\Tax\TaxPricing;
use Tally\Tax\TaxRounding;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends AccountingModel
{
    /** @use HasFactory<CompanyFactory> */
    use HasExternalReference, HasFactory;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function provision(array $attributes): self
    {
        return DB::transaction(function () use ($attributes) {
            $company = static::query()->create($attributes);

            $company->financialYears()->create([
                'name' => FinancialYear::labelForPeriod(
                    $company->financial_year_start,
                    $company->financial_year_end,
                ),
                'start_date' => $company->financial_year_start,
                'end_date' => $company->financial_year_end,
                'is_active' => true,
            ]);

            return $company->load('financialYears');
        });
    }

    protected static function booted(): void
    {
        static::created(function (Company $company): void {
            app(DefaultAccountGroups::class)->seed($company);
        });
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'data_path',
        'legal_name',
        'mailing_name',
        'address',
        'city',
        'state',
        'country',
        'pincode',
        'phone',
        'mobile',
        'fax',
        'email',
        'website',
        'gstin',
        'gst_registration_type',
        'tax_pricing',
        'tax_rounding',
        'allow_negative_stock',
        'pan',
        'sales_terms',
        'purchase_terms',
        'financial_year_start',
        'financial_year_end',
        'books_beginning_from',
        'currency_symbol',
        'currency_formal_name',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'financial_year_start' => 'date',
            'financial_year_end' => 'date',
            'books_beginning_from' => 'date',
            'gst_registration_type' => GstRegistrationType::class,
            'tax_pricing' => TaxPricing::class,
            'tax_rounding' => TaxRounding::class,
            'allow_negative_stock' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function billsOfMaterials(): HasMany
    {
        return $this->hasMany(BillOfMaterial::class);
    }

    public function financialYears(): HasMany
    {
        return $this->hasMany(FinancialYear::class);
    }

    public function accountGroups(): HasMany
    {
        return $this->hasMany(AccountGroup::class);
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(Ledger::class);
    }

    public function parties(): HasMany
    {
        return $this->hasMany(Party::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function productGroups(): HasMany
    {
        return $this->hasMany(ProductGroup::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class);
    }

    public function godowns(): HasMany
    {
        return $this->hasMany(Godown::class);
    }

    public function taxCategories(): HasMany
    {
        return $this->hasMany(TaxCategory::class);
    }

    public function taxRates(): HasMany
    {
        return $this->hasMany(TaxRate::class);
    }

    public function hsnSacs(): HasMany
    {
        return $this->hasMany(HsnSac::class);
    }

    public function taxAccounts(): HasMany
    {
        return $this->hasMany(TaxAccount::class);
    }

    public function currencies(): HasMany
    {
        return $this->hasMany(Currency::class);
    }

    public function voucherTypeMasters(): HasMany
    {
        return $this->hasMany(VoucherTypeMaster::class);
    }

    public function gstRegistrations(): HasMany
    {
        return $this->hasMany(GstRegistration::class);
    }

    public function merchantProfiles(): HasMany
    {
        return $this->hasMany(MerchantProfile::class);
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function payHeads(): HasMany
    {
        return $this->hasMany(PayHead::class);
    }

    public function costCategories(): HasMany
    {
        return $this->hasMany(CostCategory::class);
    }

    public function costCentres(): HasMany
    {
        return $this->hasMany(CostCentre::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function deductionSections(): HasMany
    {
        return $this->hasMany(DeductionSection::class);
    }

    public function stockTransactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function deleteUnused(): void
    {
        DB::transaction(function (): void {
            $id = $this->id;
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
            DB::table('acct_product_groups')->where('company_id', $id)->update(['parent_id' => null]);
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
            DB::table('acct_branches')->where('company_id', $id)->delete();
            DB::table('acct_financial_years')->where('company_id', $id)->delete();
            DB::table('acct_companies')->where('id', $id)->delete();
        });
    }

    public function periodLabel(): string
    {
        return $this->financial_year_start->format('d M Y').' – '.$this->financial_year_end->format('d M Y');
    }

    public function locationLabel(): string
    {
        return collect([$this->city, $this->state, $this->country])
            ->filter(fn (?string $part) => filled($part))
            ->implode(', ');
    }
}
