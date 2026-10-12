<?php

namespace Tally\Accounting;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\Ledger;
use Tally\Models\LedgerOpening;
use Tally\Reporting\FinancialReports;
use Tally\Reporting\LedgerBalances;

class YearEndCloseService
{
    public function __construct(
        private readonly VoucherEngine $engine,
        private readonly FinancialReports $reports,
        private readonly LedgerBalances $balances,
        private readonly PeriodLockService $periods,
    ) {}

    /**
     * Close FY: reverse P&L into retained earnings, migrate BS openings to next FY, lock year.
     */
    public function close(
        Company $company,
        FinancialYear $year,
        User $user,
        ?int $retainedEarningsLedgerId = null,
        ?FinancialYear $nextYear = null,
    ): FinancialYear {
        if ($year->company_id !== $company->id) {
            throw ValidationException::withMessages(['financial_year_id' => 'Year does not belong to this company.']);
        }

        if ($year->is_closed) {
            throw ValidationException::withMessages(['financial_year_id' => 'This financial year is already closed.']);
        }

        $branch = $company->branches()->where('is_active', true)->first()
            ?? throw ValidationException::withMessages(['branch_id' => 'An active branch is required to close the year.']);

        $retainedId = $retainedEarningsLedgerId
            ?? $company->retained_earnings_ledger_id
            ?? $this->findCapitalLedger($company)?->id;

        if (! $retainedId) {
            throw ValidationException::withMessages([
                'retained_earnings_ledger_id' => 'Select a capital / retained earnings ledger for year-end close.',
            ]);
        }

        return DB::transaction(function () use ($company, $year, $user, $branch, $retainedId, $nextYear) {
            $asOn = $year->end_date->toDateString();
            $pl = $this->reports->profitAndLoss($company, null, $year, $asOn);
            $netCents = (int) ($pl['net_cents'] ?? 0);

            $entries = $this->plClosingEntries($company, $year, $asOn);

            if ($netCents !== 0) {
                if ($netCents > 0) {
                    $entries[] = [
                        'ledger_id' => $retainedId,
                        'debit' => 0,
                        'credit' => Money::format($netCents),
                    ];
                } else {
                    $entries[] = [
                        'ledger_id' => $retainedId,
                        'debit' => Money::format(abs($netCents)),
                        'credit' => 0,
                    ];
                }
            }

            $closingVoucher = null;

            if ($entries !== []) {
                $closingVoucher = $this->engine->save(
                    $company,
                    $branch,
                    $year,
                    $user,
                    VoucherType::Journal,
                    [
                        'voucher_date' => $asOn,
                        'reference_number' => 'YE-CLOSE',
                        'narration' => 'Year-end closing for '.$year->name,
                        'entries' => $entries,
                    ],
                    true,
                );
            }

            $next = $nextYear ?? $this->ensureNextYear($company, $year);
            $this->migrateOpenings($company, $year, $next, $asOn);

            $this->periods->ensureMonthlyPeriods($company, $year);
            $this->periods->lockThrough($company, $year, $asOn, $user);

            $year->update([
                'is_closed' => true,
                'is_active' => false,
                'closed_at' => now(),
                'closed_by' => $user->id,
                'closing_voucher_id' => $closingVoucher?->id,
                'opening_profit' => Money::format($netCents),
            ]);

            if (! $next->is_active) {
                $next->update(['is_active' => true]);
            }

            return $year->fresh();
        });
    }

    private function ensureNextYear(Company $company, FinancialYear $year): FinancialYear
    {
        $start = $year->end_date->copy()->addDay();
        $end = $start->copy()->addYear()->subDay();

        return FinancialYear::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'start_date' => $start->toDateString(),
            ],
            [
                'name' => FinancialYear::labelForPeriod($start, $end),
                'end_date' => $end->toDateString(),
                'is_active' => true,
            ],
        );
    }

    private function migrateOpenings(Company $company, FinancialYear $from, FinancialYear $to, string $asOn): void
    {
        $bs = $this->reports->balanceSheet($company, null, $from, $asOn);
        $assetIds = collect($bs['assets'] ?? [])->pluck('ledger_id')->filter()->all();

        foreach (array_merge($bs['assets'] ?? [], $bs['liabilities'] ?? []) as $line) {
            $ledgerId = $line['ledger_id'] ?? null;
            if (! $ledgerId) {
                continue;
            }

            $amount = Money::cents($line['amount'] ?? 0);
            if ($amount === 0) {
                continue;
            }

            $isAsset = in_array($ledgerId, $assetIds, true);
            $type = $isAsset
                ? ($amount >= 0 ? OpeningBalanceType::Debit : OpeningBalanceType::Credit)
                : ($amount >= 0 ? OpeningBalanceType::Credit : OpeningBalanceType::Debit);

            LedgerOpening::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'ledger_id' => $ledgerId,
                    'financial_year_id' => $to->id,
                    'branch_id' => null,
                ],
                [
                    'opening_balance' => Money::format(abs($amount)),
                    'opening_balance_type' => $type,
                ],
            );
        }
    }

    /**
     * @return list<array{ledger_id:int,debit:float|int|string,credit:float|int|string}>
     */
    private function plClosingEntries(Company $company, FinancialYear $year, string $asOn): array
    {
        $lines = [];

        foreach ($this->balances->asOn($company, null, $year, $asOn) as $row) {
            if (! in_array($row['nature'], [AccountNature::Income, AccountNature::Expense], true)) {
                continue;
            }

            $net = (int) $row['debit'] - (int) $row['credit'];

            if ($net === 0) {
                continue;
            }

            $lines[] = [
                'ledger_id' => $row['ledger']->id,
                'debit' => $net < 0 ? Money::format(abs($net)) : 0,
                'credit' => $net > 0 ? Money::format($net) : 0,
            ];
        }

        return $lines;
    }

    private function findCapitalLedger(Company $company): ?Ledger
    {
        return Ledger::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('code', 'CAPITAL')
                    ->orWhere('name', 'like', '%Capital%')
                    ->orWhere('name', 'like', '%Retained%');
            })
            ->orderByRaw("CASE WHEN code = 'CAPITAL' THEN 0 ELSE 1 END")
            ->first();
    }
}
