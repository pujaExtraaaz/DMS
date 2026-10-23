<?php

namespace App\Domains\TallySync\Queue;

use App\Domains\TallySync\Templates\BooksVoucherTemplate;
use App\Domains\TallySync\Templates\MasterTemplates;
use Tally\Accounting\AccountNature;
use Tally\Accounting\OpeningBalanceType;
use Tally\Models\AccountGroup;
use Tally\Models\CostCategory;
use Tally\Models\CostCentre;
use Tally\Models\Godown;
use Tally\Models\Ledger;
use Tally\Models\Product;
use Tally\Models\ProductGroup;
use Tally\Models\TallyLink;
use Tally\Models\Unit;
use Tally\Models\Voucher;

class BooksXml
{
    public function __construct(private readonly MasterTemplates $masters) {}

    public function render(object $model, string $action): string
    {
        return match ($model::class) {
            Voucher::class => $this->voucher($model, $action),
            Ledger::class => $this->ledger($model, $action),
            AccountGroup::class => $this->group($model, $action),
            Product::class => $this->stockItem($model, $action),
            ProductGroup::class => $this->stockGroup($model, $action),
            Godown::class => $this->godown($model, $action),
            Unit::class => $this->unit($model, $action),
            CostCentre::class => $this->costCentre($model, $action),
            CostCategory::class => $this->costCategory($model, $action),
            default => throw new \InvalidArgumentException('No Tally template for '.$model::class),
        };
    }

    private function voucher(Voucher $voucher, string $action): string
    {
        return (new BooksVoucherTemplate)->render($voucher, $action);
    }

    private function ledger(Ledger $ledger, string $action): string
    {
        $ledger->loadMissing('accountGroup');
        $opening = (float) $ledger->opening_balance;
        $debit = $ledger->opening_balance_type === OpeningBalanceType::Debit
            || $ledger->opening_balance_type?->value === 'debit';
        $signed = $opening == 0.0 ? '' : number_format($debit ? -abs($opening) : abs($opening), 2, '.', '');
        $days = (int) $ledger->credit_days;

        return $this->masters->ledger(
            (string) $ledger->name,
            (string) ($ledger->accountGroup?->name ?: 'Primary'),
            $action,
            $this->currentName($ledger, 'books_ledger') ?: $ledger->name,
            $ledger->gstin,
            $ledger->address,
            $ledger->state,
            array_filter([
                'ALIAS' => $ledger->alias,
                'MAILINGNAME' => $ledger->mailing_name ?: $ledger->name,
                'COUNTRYNAME' => $ledger->country,
                'PINCODE' => $ledger->pincode,
                'LEDGERPHONE' => $ledger->phone,
                'EMAIL' => $ledger->email,
                'LEDGERCONTACT' => $ledger->contact_person,
                'INCOMETAXNUMBER' => $ledger->pan,
                'GSTREGISTRATIONTYPE' => $ledger->gst_registration_type?->label() ?: 'Regular',
                'ISBILLWISEON' => $ledger->maintain_bill_wise ? 'Yes' : 'No',
                'ISCOSTCENTRESON' => $ledger->cost_centres_applicable ? 'Yes' : 'No',
                'CREDITLIMIT' => $ledger->credit_limit !== null ? number_format((float) $ledger->credit_limit, 2, '.', '') : '',
                'BILLCREDITPERIOD' => $days > 0 ? $days.' Days' : '',
                'OPENINGBALANCE' => $signed,
                'ISINTERESTON' => $ledger->calculate_interest ? 'Yes' : 'No',
                'INTERESTRATE' => $ledger->calculate_interest ? number_format((float) $ledger->interest_rate, 2, '.', '') : '',
            ], fn ($value) => $value !== null && $value !== ''),
        );
    }

    private function group(AccountGroup $group, string $action): string
    {
        $group->loadMissing('parent');
        $nature = $group->nature instanceof AccountNature ? $group->nature : AccountNature::tryFrom((string) $group->nature);
        $revenue = in_array($nature, [AccountNature::Income, AccountNature::Expense], true);

        return $this->masters->group(
            (string) $group->name,
            (string) ($group->parent?->name ?: ''),
            $this->primaryGroup($group->name) && $action === 'Create' ? 'Alter' : $action,
            $this->currentName($group, 'books_group') ?: $group->name,
            array_filter([
                'PARENT' => $group->parent?->name ?: '',
                'ISREVENUE' => $revenue ? 'Yes' : 'No',
                'AFFECTSGROSSPROFIT' => $group->affects_gross_profit ? 'Yes' : 'No',
                'ISDEEMEDPOSITIVE' => in_array($nature, [AccountNature::Asset, AccountNature::Expense], true) ? 'Yes' : 'No',
            ], fn ($value) => $value !== null && $value !== ''),
        );
    }

    private function stockItem(Product $product, string $action): string
    {
        $product->loadMissing(['primaryUnit', 'productGroup', 'hsnSac', 'taxRate']);
        $unit = $product->primaryUnit?->symbol ?: $product->primaryUnit?->name ?: 'Nos';
        $rate = (float) ($product->taxRate?->igst_rate ?: ((float) $product->taxRate?->cgst_rate + (float) $product->taxRate?->sgst_rate));
        $qty = trim(rtrim(rtrim(number_format((float) $product->opening_quantity, 4, '.', ''), '0'), '.').' '.$unit);

        return $this->masters->stockItem(
            (string) $product->name,
            $unit,
            $action,
            $this->currentName($product, 'books_stock_item') ?: $product->name,
            $product->hsnSac?->code,
            $product->description,
            $rate,
            array_filter([
                'PARENT' => $product->productGroup?->name ?: 'Primary',
                'ADDITIONALNAME' => $product->alias,
                'PARTNO' => $product->part_number ?: $product->code,
                'GSTTYPEOFSUPPLY' => strcasecmp((string) $product->type_of_supply, 'service') === 0 ? 'Services' : 'Goods',
                'OPENINGBALANCE' => (float) $product->opening_quantity != 0.0 ? $qty : '',
                'OPENINGRATE' => (float) $product->opening_rate > 0 ? number_format((float) $product->opening_rate, 2, '.', '').'/'.$unit : '',
                'OPENINGVALUE' => (float) $product->opening_value != 0.0 ? number_format(-abs((float) $product->opening_value), 2, '.', '') : '',
                'REORDERLEVEL' => (float) $product->reorder_level > 0 ? rtrim(rtrim(number_format((float) $product->reorder_level, 4, '.', ''), '0'), '.').' '.$unit : '',
                'MINORDERQTY' => (float) $product->minimum_stock > 0 ? rtrim(rtrim(number_format((float) $product->minimum_stock, 4, '.', ''), '0'), '.').' '.$unit : '',
                'ISBATCHWISEON' => $product->track_batch ? 'Yes' : 'No',
                'HASMFGDATE' => $product->track_batch ? 'Yes' : 'No',
                'ISPERISHABLEON' => $product->track_batch ? 'Yes' : 'No',
                'STANDARDPRICELIST.LIST' => '',
                'STANDARDCOST' => (float) $product->purchase_rate > 0 ? number_format((float) $product->purchase_rate, 2, '.', '') : '',
                'STANDARDPRICE' => (float) $product->sales_rate > 0 ? number_format((float) $product->sales_rate, 2, '.', '') : '',
            ], fn ($value) => $value !== null && $value !== ''),
        );
    }

    private function stockGroup(ProductGroup $group, string $action): string
    {
        $group->loadMissing('parent');

        return $this->masters->stockGroup(
            (string) $group->name,
            (string) ($group->parent?->name ?: 'Primary'),
            $action,
            $this->currentName($group, 'books_stock_group') ?: $group->name,
        );
    }

    private function godown(Godown $godown, string $action): string
    {
        $godown->loadMissing('parent');

        return $this->masters->godown(
            (string) $godown->name,
            $action,
            $this->currentName($godown, 'books_godown') ?: $godown->name,
            array_filter([
                'PARENT' => $godown->parent?->name ?: 'Primary',
                'ADDRESS' => $godown->address,
            ], fn ($value) => $value !== null && $value !== ''),
        );
    }

    private function unit(Unit $unit, string $action): string
    {
        $name = $unit->symbol ?: $unit->name;

        return $this->masters->unit($name, $action, $this->currentName($unit, 'books_unit') ?: $name, array_filter([
            'ORIGINALNAME' => $unit->name,
            'DECIMALPLACES' => (string) max(0, (int) $unit->decimal_places),
            'ISSIMPLEUNIT' => 'Yes',
            'REPORTINGUQC' => $unit->gst_uqc,
        ], fn ($value) => $value !== null && $value !== ''));
    }

    private function costCentre(CostCentre $centre, string $action): string
    {
        $centre->loadMissing(['parent', 'category']);

        return $this->masters->costCentre(
            (string) $centre->name,
            (string) ($centre->category?->name ?: 'Primary Cost Category'),
            $action,
            $this->currentName($centre, 'books_cost_centre') ?: $centre->name,
            array_filter([
                'PARENT' => $centre->parent?->name ?: '',
            ], fn ($value) => $value !== null && $value !== ''),
        );
    }

    private function costCategory(CostCategory $category, string $action): string
    {
        return $this->masters->costCategory(
            (string) $category->name,
            $action,
            $this->currentName($category, 'books_cost_category') ?: $category->name,
        );
    }

    private function currentName(object $model, string $type): ?string
    {
        if (! method_exists($model, 'getKey')) {
            return null;
        }

        return TallyLink::query()
            ->where('linkable_type', $model::class)
            ->where('linkable_id', $model->getKey())
            ->where('tally_type', $type)
            ->value('remote_id')
            ? null
            : null;
    }

    private function primaryGroup(string $name): bool
    {
        return in_array($name, [
            'Capital Account', 'Current Assets', 'Current Liabilities', 'Fixed Assets', 'Investments',
            'Loans (Liability)', 'Loans', 'Suspense Account', 'Branch / Divisions', 'Misc. Expenses (ASSET)',
            'Bank Accounts', 'Cash-in-Hand', 'Deposits (Asset)', 'Loans & Advances (Asset)', 'Stock-in-Hand',
            'Sundry Debtors', 'Duties & Taxes', 'Provisions', 'Sundry Creditors', 'Bank OD A/c',
            'Secured Loans', 'Unsecured Loans', 'Direct Expenses', 'Indirect Expenses', 'Direct Incomes',
            'Indirect Incomes', 'Purchase Accounts', 'Sales Accounts',
        ], true);
    }
}