<?php

namespace Tally\Accounting;

use Tally\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Ledger choices for Accounting Features. Each field only lists ledgers
 * from the account groups that field posts to.
 */
class FeatureLedgerOptions
{
    /**
     * Group codes that may appear, including ledgers in a child of that group.
     *
     * @var array<string, list<string>>
     */
    private const ALLOW = [
        'retained_earnings_ledger_id' => ['CAPITAL'],
        'customer_advance_ledger_id' => ['CURRENT_LIABILITIES'],
        'bad_debt_ledger_id' => ['INDIRECT_EXPENSES'],
        'doubtful_debt_ledger_id' => ['CURRENT_LIABILITIES'],
        'retention_ledger_id' => ['CURRENT_LIABILITIES'],
        'commission_expense_ledger_id' => ['INDIRECT_EXPENSES'],
        'commission_payable_ledger_id' => ['CURRENT_LIABILITIES'],
        'round_off_ledger_id' => ['INDIRECT_EXPENSES', 'INDIRECT_INCOMES'],
    ];

    /**
     * Child groups that share a parent but are a different kind of account.
     *
     * @var array<string, list<string>>
     */
    private const EXCLUDE = [
        'customer_advance_ledger_id' => ['CREDITORS', 'DUTIES'],
        'doubtful_debt_ledger_id' => ['CREDITORS', 'DUTIES'],
        'retention_ledger_id' => ['CREDITORS', 'DUTIES'],
        'commission_payable_ledger_id' => ['CREDITORS', 'DUTIES'],
    ];

    /**
     * @return array<string, Collection<int, \Tally\Models\Ledger>>
     */
    public function forCompany(Company $company): array
    {
        $groups = $company->accountGroups()->get(['id', 'parent_id', 'code'])->keyBy('id');
        $ledgers = $company->ledgers()
            ->with('accountGroup:id,name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'account_group_id']);
        $options = [];

        foreach (self::ALLOW as $field => $allow) {
            $exclude = self::EXCLUDE[$field] ?? [];
            $options[$field] = $ledgers->filter(function ($ledger) use ($groups, $allow, $exclude) {
                $codes = $this->codes((int) $ledger->account_group_id, $groups);

                return array_intersect($codes, $allow) !== []
                    && array_intersect($codes, $exclude) === [];
            })->values();
        }

        return $options;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(Company $company): array
    {
        $rules = [];

        foreach ($this->forCompany($company) as $field => $ledgers) {
            $rules[$field] = ['nullable', 'integer', Rule::in($ledgers->pluck('id')->all())];
        }

        return $rules;
    }

    /**
     * @param  Collection<int|string, mixed>  $groups
     * @return list<string>
     */
    private function codes(int $groupId, Collection $groups): array
    {
        $codes = [];
        $guard = 0;

        while ($groupId > 0 && $guard < 20) {
            $group = $groups->get($groupId);

            if (! $group) {
                break;
            }

            $codes[] = (string) $group->code;
            $groupId = (int) $group->parent_id;
            $guard++;
        }

        return $codes;
    }
}
