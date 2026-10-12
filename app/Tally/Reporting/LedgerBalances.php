<?php

namespace Tally\Reporting;

use Tally\Accounting\AccountNature;
use Tally\Accounting\LedgerOpeningBook;
use Tally\Accounting\Money;
use Tally\Accounting\VoucherStatus;
use Tally\Accounting\VoucherType;
use Tally\Models\AccountGroup;
use Tally\Models\BillAllocation;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\Ledger;
use Tally\Models\VoucherEntry;
use Tally\Support\Queries\DateRange;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Ledger balances from opening balances plus posted voucher lines only.
 */
class LedgerBalances
{
    /**
     * @return Collection<int, array{ledger: Ledger, nature: AccountNature, codes: list<string>, debit: int, credit: int}>
     */
    public function asOn(Company $company, ?int $branchId, FinancialYear $year, string $to, bool $cashBasis = false): Collection
    {
        $groups = AccountGroup::query()->where('company_id', $company->id)->get()->keyBy('id');
        $cashTypes = [
            VoucherType::Payment->value,
            VoucherType::Receipt->value,
            VoucherType::Contra->value,
            VoucherType::Transfer->value,
        ];

        $movements = VoucherEntry::query()
            ->join('acct_vouchers', 'acct_vouchers.id', '=', 'acct_voucher_entries.voucher_id')
            ->where('acct_vouchers.company_id', $company->id)
            ->where('acct_vouchers.financial_year_id', $year->id)
            ->where('acct_vouchers.status', VoucherStatus::Posted->value)
            ->tap(fn ($query) => DateRange::apply($query, 'acct_vouchers.voucher_date', $year->start_date->toDateString(), $to))
            ->when($branchId, fn ($query) => $query->where('acct_vouchers.branch_id', $branchId))
            ->when($cashBasis, fn ($query) => $query->whereIn('acct_vouchers.voucher_type', $cashTypes))
            ->groupBy('acct_voucher_entries.ledger_id')
            ->select('acct_voucher_entries.ledger_id')
            ->selectRaw('round(sum(acct_voucher_entries.debit), 2) as movement_debit')
            ->selectRaw('round(sum(acct_voucher_entries.credit), 2) as movement_credit')
            ->get()
            ->keyBy('ledger_id');

        // Cash-basis recognition: settle bill allocations against original income/expense ledgers
        // from the invoice/voucher that raised the bill (not the party control ledger).
        $cashRecognized = $cashBasis
            ? $this->cashRecognizedByLedger($company, $branchId, $year, $to)
            : collect();

        $openings = app(LedgerOpeningBook::class);

        return $company->ledgers()->with('accountGroup')->orderBy('name')->get()->map(function (Ledger $ledger) use ($groups, $movements, $openings, $year, $branchId, $cashRecognized, $cashBasis) {
            $signedOpening = $openings->signedCents($ledger, $year, $branchId);
            // On cash basis, skip accrual openings on party/control ledgers (non cash/bank).
            if ($cashBasis && ! $ledger->isCashOrBank() && ($ledger->is_control_account || $ledger->isCustomer() || $ledger->isSupplier())) {
                $signedOpening = 0;
            }
            $openingDebit = $signedOpening > 0 ? $signedOpening : 0;
            $openingCredit = $signedOpening < 0 ? -$signedOpening : 0;
            $movement = $movements->get($ledger->id);
            $recognized = (int) ($cashRecognized[$ledger->id] ?? 0);
            $signed = ($openingDebit - $openingCredit)
                + Money::cents((string) ($movement->movement_debit ?? 0))
                - Money::cents((string) ($movement->movement_credit ?? 0))
                + $recognized;

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
     * Map cash settlements to the income/expense ledger on the original bill's voucher.
     * Positive = debit increase (expense), negative = credit increase (income).
     *
     * @return Collection<int, int> ledger_id => signed cents
     */
    private function cashRecognizedByLedger(Company $company, ?int $branchId, FinancialYear $year, string $to): Collection
    {
        $rows = BillAllocation::query()
            ->join('acct_bills', 'acct_bills.id', '=', 'acct_bill_allocations.bill_id')
            ->join('acct_vouchers as settle_voucher', 'settle_voucher.id', '=', 'acct_bill_allocations.voucher_id')
            ->leftJoin('acct_voucher_entries as bill_entry', 'bill_entry.id', '=', 'acct_bills.voucher_entry_id')
            ->leftJoin('acct_vouchers as bill_voucher', 'bill_voucher.id', '=', 'bill_entry.voucher_id')
            ->where('settle_voucher.company_id', $company->id)
            ->where('settle_voucher.financial_year_id', $year->id)
            ->where('settle_voucher.status', VoucherStatus::Posted->value)
            ->whereIn('settle_voucher.voucher_type', [VoucherType::Payment->value, VoucherType::Receipt->value])
            ->tap(fn ($query) => DateRange::apply($query, 'settle_voucher.voucher_date', $year->start_date->toDateString(), $to))
            ->when($branchId, fn ($query) => $query->where('settle_voucher.branch_id', $branchId))
            ->whereNotNull('bill_voucher.id')
            ->select([
                'acct_bill_allocations.amount',
                'acct_bills.side',
                'bill_voucher.id as bill_voucher_id',
            ])
            ->get();

        $byLedger = [];

        foreach ($rows as $row) {
            $incomeExpense = VoucherEntry::query()
                ->join('acct_ledgers', 'acct_ledgers.id', '=', 'acct_voucher_entries.ledger_id')
                ->join('acct_account_groups', 'acct_account_groups.id', '=', 'acct_ledgers.account_group_id')
                ->where('acct_voucher_entries.voucher_id', $row->bill_voucher_id)
                ->whereIn('acct_account_groups.nature', [AccountNature::Income->value, AccountNature::Expense->value])
                ->orderByDesc(DB::raw('acct_voucher_entries.debit + acct_voucher_entries.credit'))
                ->select('acct_voucher_entries.ledger_id', 'acct_account_groups.nature')
                ->first();

            if (! $incomeExpense) {
                continue;
            }

            $amount = Money::cents((string) $row->amount);
            // Receipt against debtor bill → recognize income (credit); payment against creditor → expense (debit).
            $signed = $incomeExpense->nature === AccountNature::Income->value
                ? -$amount
                : $amount;

            $byLedger[$incomeExpense->ledger_id] = ($byLedger[$incomeExpense->ledger_id] ?? 0) + $signed;
        }

        return collect($byLedger);
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
