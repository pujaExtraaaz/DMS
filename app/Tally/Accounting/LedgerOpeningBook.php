<?php

namespace Tally\Accounting;

use Tally\Models\FinancialYear;
use Tally\Models\Ledger;
use Tally\Models\LedgerOpening;

/**
 * Opening balances belong to one financial year.
 * A company-level opening is not repeated on a branch that does not own it.
 */
class LedgerOpeningBook
{
    public function signedCents(Ledger $ledger, FinancialYear $year, ?int $branchId): int
    {
        $row = $this->row($ledger, $year, $branchId);

        if ($row) {
            return $this->sign($row->opening_balance_type, Money::cents((string) $row->opening_balance));
        }

        if ($branchId !== null) {
            return 0;
        }

        if ($this->hasAny($ledger) || ! $this->isEarliest($ledger, $year)) {
            return 0;
        }

        return $this->sign($ledger->opening_balance_type, Money::cents((string) $ledger->opening_balance));
    }

    public function remember(Ledger $ledger, FinancialYear $year): void
    {
        if ($ledger->company_id !== $year->company_id) {
            return;
        }

        $existing = LedgerOpening::query()
            ->where('ledger_id', $ledger->id)
            ->where('financial_year_id', $year->id)
            ->whereNull('branch_id')
            ->first();

        $values = [
            'company_id' => $ledger->company_id,
            'opening_balance' => $ledger->opening_balance,
            'opening_balance_type' => $ledger->opening_balance_type,
        ];

        if ($existing) {
            $existing->update($values);

            return;
        }

        LedgerOpening::query()->create([
            'ledger_id' => $ledger->id,
            'financial_year_id' => $year->id,
            'branch_id' => null,
            ...$values,
        ]);
    }

    private function row(Ledger $ledger, FinancialYear $year, ?int $branchId): ?LedgerOpening
    {
        return LedgerOpening::query()
            ->where('ledger_id', $ledger->id)
            ->where('financial_year_id', $year->id)
            ->when(
                $branchId,
                fn ($query) => $query->where('branch_id', $branchId),
                fn ($query) => $query->whereNull('branch_id'),
            )
            ->first();
    }

    private function hasAny(Ledger $ledger): bool
    {
        return LedgerOpening::query()->where('ledger_id', $ledger->id)->exists();
    }

    private function isEarliest(Ledger $ledger, FinancialYear $year): bool
    {
        $earliest = FinancialYear::query()
            ->where('company_id', $ledger->company_id)
            ->orderBy('start_date')
            ->orderBy('id')
            ->value('id');

        return (int) $earliest === (int) $year->id;
    }

    private function sign(OpeningBalanceType $type, int $cents): int
    {
        return $type === OpeningBalanceType::Credit ? -$cents : $cents;
    }
}
