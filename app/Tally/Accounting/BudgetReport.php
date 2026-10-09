<?php

namespace Tally\Accounting;

use Tally\Models\AccountGroup;
use Tally\Models\Budget;
use Tally\Models\VoucherEntry;
use Tally\Support\Queries\DateRange;

class BudgetReport
{
    /**
     * @return array{budget: string, actual: string, variance: string, debit: string, credit: string}
     */
    public function compare(Budget $budget): array
    {
        $budget->loadMissing(['ledger.accountGroup', 'accountGroup', 'costCentre']);
        $groupIds = $this->groupIds($budget);
        $entries = VoucherEntry::query()
            ->with('ledger.accountGroup')
            ->whereHas('voucher', function ($query) use ($budget) {
                $query->where('company_id', $budget->company_id)
                    ->where('status', VoucherStatus::Posted)
                    ->tap(fn ($query) => DateRange::apply($query, 'voucher_date', $budget->period_start->toDateString(), $budget->period_end->toDateString()));
            })
            ->when($budget->ledger_id, fn ($query) => $query->where('ledger_id', $budget->ledger_id))
            ->when($groupIds !== [], fn ($query) => $query->whereHas(
                'ledger',
                fn ($ledger) => $ledger->whereIn('account_group_id', $groupIds)
            ))
            ->when($budget->cost_centre_id, fn ($query) => $query->where('cost_centre_id', $budget->cost_centre_id))
            ->get();

        $debit = 0;
        $credit = 0;

        foreach ($entries as $entry) {
            $debit += Money::cents((string) $entry->debit);
            $credit += Money::cents((string) $entry->credit);
        }

        $nature = $budget->ledger?->accountGroup?->nature ?? $budget->accountGroup?->nature;
        $actual = match ($nature) {
            AccountNature::Income, AccountNature::Liability => $credit - $debit,
            default => $debit - $credit,
        };
        $target = Money::cents((string) $budget->amount);

        return [
            'budget' => Money::format($target),
            'actual' => Money::format($actual),
            'variance' => Money::format($target - $actual),
            'debit' => Money::format($debit),
            'credit' => Money::format($credit),
        ];
    }

    /**
     * @return list<int>
     */
    private function groupIds(Budget $budget): array
    {
        if (! $budget->account_group_id) {
            return [];
        }

        $ids = [$budget->account_group_id];
        $pending = [$budget->account_group_id];

        while ($pending !== []) {
            $children = AccountGroup::query()->whereIn('parent_id', $pending)->pluck('id')->all();
            $pending = array_values(array_diff($children, $ids));
            $ids = array_values(array_unique([...$ids, ...$children]));
        }

        return $ids;
    }
}
