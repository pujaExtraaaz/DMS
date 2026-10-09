<?php

namespace Tally\Inventory;

use Tally\Accounting\AccountNature;
use Tally\Accounting\OpeningBalanceType;
use Tally\Models\AccountGroup;
use Tally\Models\BillOfMaterial;
use Tally\Models\Company;
use Tally\Models\Ledger;
use Tally\Models\Product;

/**
 * Stock, raw material, finished goods, and cost of goods sold ledgers.
 * A finished product on a bill of materials is finished goods.
 * Any other product in the MFG group is raw material.
 * Everything else is stock-in-hand.
 */
class InventoryAccounts
{
    public function stock(Company $company): Ledger
    {
        return $this->ledger($company, 'STOCK', 'Stock-in-hand', $this->stockGroup($company), OpeningBalanceType::Debit);
    }

    public function raw(Company $company): Ledger
    {
        return $this->ledger($company, 'RAW', 'Raw materials', $this->stockGroup($company), OpeningBalanceType::Debit);
    }

    public function finished(Company $company): Ledger
    {
        return $this->ledger($company, 'FG', 'Finished goods', $this->stockGroup($company), OpeningBalanceType::Debit);
    }

    public function cogs(Company $company): Ledger
    {
        return $this->ledger($company, 'COGS', 'Cost of goods sold', $this->group($company, 'DIRECT_EXPENSES'), OpeningBalanceType::Debit);
    }

    public function openingReserve(Company $company): Ledger
    {
        return $this->ledger($company, 'OPENING_STOCK', 'Opening stock', $this->group($company, 'CAPITAL'), OpeningBalanceType::Credit);
    }

    public function adjustment(Company $company): Ledger
    {
        return $this->ledger($company, 'STOCK_ADJ', 'Stock introduced', $this->group($company, 'CAPITAL'), OpeningBalanceType::Credit);
    }

    public function ledgerFor(Product $product): Ledger
    {
        $product->loadMissing('productGroup');

        $finished = BillOfMaterial::query()
            ->where('company_id', $product->company_id)
            ->where('finished_product_id', $product->id)
            ->exists();

        if ($finished) {
            return $this->finished($product->company);
        }

        if ($product->productGroup?->code === 'MFG') {
            return $this->raw($product->company);
        }

        return $this->stock($product->company);
    }

    /**
     * @return list<string>
     */
    public function assetCodes(): array
    {
        return ['STOCK', 'RAW', 'FG'];
    }

    private function stockGroup(Company $company): AccountGroup
    {
        $existing = $company->accountGroups()->where('code', 'STOCK_IN_HAND')->first();

        if ($existing) {
            return $existing;
        }

        $parent = $company->accountGroups()->where('code', 'CURRENT_ASSETS')->first();

        return $company->accountGroups()->create([
            'parent_id' => $parent?->id,
            'name' => 'Stock-in-hand',
            'code' => 'STOCK_IN_HAND',
            'nature' => AccountNature::Asset,
            'sort_order' => 40,
            'is_system' => true,
            'is_active' => true,
        ]);
    }

    private function group(Company $company, string $code): AccountGroup
    {
        $group = $company->accountGroups()->where('code', $code)->first();

        if (! $group) {
            throw new \RuntimeException('Account group '.$code.' is missing for '.$company->name.'.');
        }

        return $group;
    }

    private function ledger(Company $company, string $code, string $name, AccountGroup $group, OpeningBalanceType $side): Ledger
    {
        $ledger = $company->ledgers()->where('code', $code)->first();

        if ($ledger) {
            return $ledger;
        }

        return $company->ledgers()->create([
            'account_group_id' => $group->id,
            'name' => $name,
            'code' => $code,
            'opening_balance' => '0.00',
            'opening_balance_type' => $side,
            'is_active' => true,
            'is_system' => true,
        ]);
    }
}
