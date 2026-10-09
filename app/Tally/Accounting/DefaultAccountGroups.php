<?php

namespace Tally\Accounting;

use Tally\Models\Company;

class DefaultAccountGroups
{
    /**
     * Standard chart. Child groups sit under the Tally-style primary groups.
     *
     * @return list<array{name: string, code: string, nature: AccountNature, children?: list<array<string, mixed>>}>
     */
    public function chart(): array
    {
        return [
            ['name' => 'Capital Account', 'code' => 'CAPITAL', 'nature' => AccountNature::Liability],
            ['name' => 'Current Assets', 'code' => 'CURRENT_ASSETS', 'nature' => AccountNature::Asset, 'children' => [
                ['name' => 'Bank Accounts', 'code' => 'BANK', 'nature' => AccountNature::Asset],
                ['name' => 'Cash-in-Hand', 'code' => 'CASH', 'nature' => AccountNature::Asset],
                ['name' => 'Sundry Debtors', 'code' => 'DEBTORS', 'nature' => AccountNature::Asset],
            ]],
            ['name' => 'Current Liabilities', 'code' => 'CURRENT_LIABILITIES', 'nature' => AccountNature::Liability, 'children' => [
                ['name' => 'Duties & Taxes', 'code' => 'DUTIES', 'nature' => AccountNature::Liability],
                ['name' => 'Sundry Creditors', 'code' => 'CREDITORS', 'nature' => AccountNature::Liability],
            ]],
            ['name' => 'Fixed Assets', 'code' => 'FIXED_ASSETS', 'nature' => AccountNature::Asset],
            ['name' => 'Investments', 'code' => 'INVESTMENTS', 'nature' => AccountNature::Asset],
            ['name' => 'Loans', 'code' => 'LOANS', 'nature' => AccountNature::Liability],
            ['name' => 'Direct Expenses', 'code' => 'DIRECT_EXPENSES', 'nature' => AccountNature::Expense],
            ['name' => 'Indirect Expenses', 'code' => 'INDIRECT_EXPENSES', 'nature' => AccountNature::Expense],
            ['name' => 'Direct Incomes', 'code' => 'DIRECT_INCOMES', 'nature' => AccountNature::Income],
            ['name' => 'Indirect Incomes', 'code' => 'INDIRECT_INCOMES', 'nature' => AccountNature::Income],
            ['name' => 'Purchase Accounts', 'code' => 'PURCHASE', 'nature' => AccountNature::Expense],
            ['name' => 'Sales Accounts', 'code' => 'SALES', 'nature' => AccountNature::Income],
        ];
    }

    public function seed(Company $company): void
    {
        if ($company->accountGroups()->exists()) {
            return;
        }

        $order = 0;
        $this->insert($company, $this->chart(), null, $order);
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     */
    private function insert(Company $company, array $nodes, ?int $parentId, int &$order): void
    {
        foreach ($nodes as $node) {
            $order++;

            $group = $company->accountGroups()->create([
                'parent_id' => $parentId,
                'name' => $node['name'],
                'code' => $node['code'],
                'nature' => $node['nature'],
                'sort_order' => $order,
                'is_system' => true,
                'is_active' => true,
            ]);

            if (! empty($node['children'])) {
                $this->insert($company, $node['children'], $group->id, $order);
            }
        }
    }
}
