<?php

namespace Tally\Reporting;

use Tally\Accounting\AccountNature;
use Tally\Accounting\LedgerOpeningBook;
use Tally\Accounting\Money;
use Tally\Accounting\VoucherStatus;
use Tally\Models\AccountGroup;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\Ledger;
use Tally\Models\VoucherEntry;
use Tally\Support\Queries\DateRange;
use Illuminate\Support\Collection;

/**
 * Ledger balances from opening balances plus posted voucher lines only.
 */
class LedgerBalances
{
    /**
     * @return Collection<int, array{ledger: Ledger, nature: AccountNature, codes: list<string>, debit: int, credit: int}>
     */
    public function asOn(Company $company, ?int $branchId, FinancialYear $year, string $to): Collection
    {
        $groups = AccountGroup::query()->where('company_id', $company->id)->get()->keyBy('id');
        $movements = VoucherEntry::query()
            ->join('acct_vouchers', 'acct_vouchers.id', '=', 'acct_voucher_entries.voucher_id')
            ->where('acct_vouchers.company_id', $company->id)
            ->where('acct_vouchers.financial_year_id', $year->id)
            ->where('acct_vouchers.status', VoucherStatus::Posted->value)
            ->tap(fn ($query) => DateRange::apply($query, 'acct_vouchers.voucher_date', $year->start_date->toDateString(), $to))
            ->when($branchId, fn ($query) => $query->where('acct_vouchers.branch_id', $branchId))
            ->groupBy('acct_voucher_entries.ledger_id')
            ->select('acct_voucher_entries.ledger_id')
            ->selectRaw('round(sum(acct_voucher_entries.debit), 2) as movement_debit')
            ->selectRaw('round(sum(acct_voucher_entries.credit), 2) as movement_credit')
            ->get()
            ->keyBy('ledger_id');

        $openings = app(LedgerOpeningBook::class);

        return $company->ledgers()->with('accountGroup')->orderBy('name')->get()->map(function (Ledger $ledger) use ($groups, $movements, $openings, $year, $branchId) {
            $signedOpening = $openings->signedCents($ledger, $year, $branchId);
            $openingDebit = $signedOpening > 0 ? $signedOpening : 0;
            $openingCredit = $signedOpening < 0 ? -$signedOpening : 0;
            $movement = $movements->get($ledger->id);
            $signed = ($openingDebit - $openingCredit)
                + Money::cents((string) ($movement->movement_debit ?? 0))
                - Money::cents((string) ($movement->movement_credit ?? 0));

            return [
                'ledger' => $ledger,
                'nature' => $ledger->accountGroup?->nature ?? AccountNature::Asset,
                'codes' => $this->codes($ledger, $groups),
                'debit' => $signed > 0 ? $signed : 0,
                'credit' => $signed < 0 ? -$signed : 0,
            ];
        });
    }

    /**
     * @param  Collection<int, AccountGroup>  $groups
     * @return list<string>
     */
    private function codes(Ledger $ledger, Collection $groups): array
    {
        $codes = [];
        $id = $ledger->account_group_id;
        $guard = 0;

        while ($id && $guard < 50) {
            $group = $groups->get($id);

            if (! $group) {
                break;
            }

            if ($group->code) {
                $codes[] = $group->code;
            }

            $id = $group->parent_id;
            $guard++;
        }

        return $codes;
    }
}
