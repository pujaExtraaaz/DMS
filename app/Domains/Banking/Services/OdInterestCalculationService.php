<?php

namespace App\Domains\Banking\Services;

use App\Domains\Banking\Models\BankAccountTransaction;
use App\Domains\Banking\Models\OdAccount;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class OdInterestCalculationService
{
    /**
     * Compute OD report data and interest calculation for a specific OD account over a date range.
     *
     * @return array{
     *     odAccount: OdAccount,
     *     fromDate: string,
     *     toDate: string,
     *     openingUtilized: float,
     *     currentUtilized: float,
     *     odLimit: float,
     *     availableOd: float,
     *     utilizationPct: float,
     *     isExceeded: bool,
     *     exceededAmount: float,
     *     interestRate: float,
     *     totalDebit: float,
     *     totalCredit: float,
     *     totalPeriodInterest: float,
     *     rows: Collection
     * }
     */
    public function calculate(
        OdAccount $odAccount,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?string $search = null,
        ?string $typeFilter = null
    ): array {
        $from = filled($fromDate) ? Carbon::parse($fromDate)->startOfDay() : null;
        $to = filled($toDate) ? Carbon::parse($toDate)->endOfDay() : null;

        $odLimit = (float) $odAccount->od_limit;
        $annualRate = (float) $odAccount->interest_rate;

        // 1. Calculate opening utilized balance prior to fromDate
        $openingUtilized = 0.0;
        if ($from) {
            $openingDebits = (float) BankAccountTransaction::query()
                ->where('company_id', $odAccount->company_id)
                ->where('account_number', $odAccount->account_number)
                ->whereDate('transaction_date', '<', $from->toDateString())
                ->sum('debit');

            $openingCredits = (float) BankAccountTransaction::query()
                ->where('company_id', $odAccount->company_id)
                ->where('account_number', $odAccount->account_number)
                ->whereDate('transaction_date', '<', $from->toDateString())
                ->sum('credit');

            $openingUtilized = round($openingDebits - $openingCredits, 2);
        }

        // 2. Fetch all transactions in the selected date range (or all if unbounded)
        $txnsQuery = BankAccountTransaction::query()
            ->where('company_id', $odAccount->company_id)
            ->where('account_number', $odAccount->account_number)
            ->when($from, fn ($q) => $q->whereDate('transaction_date', '>=', $from->toDateString()))
            ->when($to, fn ($q) => $q->whereDate('transaction_date', '<=', $to->toDateString()))
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc');

        $allPeriodTxns = $txnsQuery->get();

        // 3. Build interest calculation chronological rows
        $calculatedRows = collect();
        $runningUtilized = $openingUtilized;
        $cumulativeInterest = 0.0;
        $totalPeriodInterest = 0.0;
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        // If there is an opening balance and from date is specified, create an opening row
        if ($from && $openingUtilized != 0 && $allPeriodTxns->isNotEmpty()) {
            $firstTxnDate = Carbon::parse($allPeriodTxns->first()->transaction_date);
            $openingDays = max(0, $from->diffInDays($firstTxnDate));

            if ($openingDays > 0) {
                $dailyInt = round($openingUtilized * $annualRate / 365 / 100, 4);
                $periodInt = round($dailyInt * $openingDays, 2);
                $cumulativeInterest += $periodInt;
                $totalPeriodInterest += $periodInt;

                $calculatedRows->push([
                    'id' => null,
                    'is_opening' => true,
                    'date' => $from->copy(),
                    'transaction_no' => 'OPENING',
                    'description' => 'Opening OD Utilized Balance',
                    'transaction_type' => 'opening_balance',
                    'debit' => $openingUtilized > 0 ? $openingUtilized : 0.0,
                    'credit' => $openingUtilized < 0 ? abs($openingUtilized) : 0.0,
                    'running_balance' => $openingUtilized,
                    'od_utilized' => $openingUtilized,
                    'od_limit' => $odLimit,
                    'available_od' => $odLimit > 0 ? ($openingUtilized > 0 ? max(0.0, round($odLimit - $openingUtilized, 2)) : $odLimit) : 0.0,
                    'is_exceeded' => $odLimit > 0 && $openingUtilized > $odLimit,
                    'interest_rate' => $annualRate,
                    'days' => $openingDays,
                    'daily_interest' => $dailyInt,
                    'row_interest' => $periodInt,
                    'cumulative_interest' => $cumulativeInterest,
                ]);
            }
        } elseif ($from && $openingUtilized != 0 && $allPeriodTxns->isEmpty()) {
            $periodDays = $to ? max(1, $from->diffInDays($to) + 1) : 1;
            $dailyInt = round($openingUtilized * $annualRate / 365 / 100, 4);
            $periodInt = round($dailyInt * $periodDays, 2);
            $cumulativeInterest += $periodInt;
            $totalPeriodInterest += $periodInt;

            $calculatedRows->push([
                'id' => null,
                'is_opening' => true,
                'date' => $from->copy(),
                'transaction_no' => 'OPENING',
                'description' => 'Opening OD Utilized Balance',
                'transaction_type' => 'opening_balance',
                'debit' => $openingUtilized > 0 ? $openingUtilized : 0.0,
                'credit' => $openingUtilized < 0 ? abs($openingUtilized) : 0.0,
                'running_balance' => $openingUtilized,
                'od_utilized' => $openingUtilized,
                'od_limit' => $odLimit,
                'available_od' => $odLimit > 0 ? ($openingUtilized > 0 ? max(0.0, round($odLimit - $openingUtilized, 2)) : $odLimit) : 0.0,
                'is_exceeded' => $odLimit > 0 && $openingUtilized > $odLimit,
                'interest_rate' => $annualRate,
                'days' => $periodDays,
                'daily_interest' => $dailyInt,
                'row_interest' => $periodInt,
                'cumulative_interest' => $cumulativeInterest,
            ]);
        }

        // Process period transactions
        $count = $allPeriodTxns->count();
        for ($i = 0; $i < $count; $i++) {
            $txn = $allPeriodTxns[$i];
            $txnDebit = (float) $txn->debit;
            $txnCredit = (float) $txn->credit;

            $totalDebit += $txnDebit;
            $totalCredit += $txnCredit;

            $runningUtilized = round($runningUtilized + $txnDebit - $txnCredit, 2);
            $availableOd = $odLimit > 0
                ? ($runningUtilized > 0 ? max(0.0, round($odLimit - $runningUtilized, 2)) : $odLimit)
                : 0.0;
            $isRowExceeded = $odLimit > 0 && $runningUtilized > $odLimit;

            $currentTxnDate = Carbon::parse($txn->transaction_date);

            // Determine days utilized for this specific row:
            if ($i + 1 < $count) {
                $nextTxnDate = Carbon::parse($allPeriodTxns[$i + 1]->transaction_date);
                if ($nextTxnDate->greaterThan($currentTxnDate)) {
                    $days = (int) $currentTxnDate->diffInDays($nextTxnDate);
                } else {
                    $days = 0;
                }
            } else {
                // Last transaction in the period:
                if ($to) {
                    $days = $to->greaterThanOrEqualTo($currentTxnDate) ? (int) $currentTxnDate->diffInDays($to) + 1 : 1;
                } else {
                    $days = 1;
                }
            }

            // Daily Interest: Simple Daily = OD Utilized * Rate / 365 / 100
            $dailyInterest = ($runningUtilized > 0 && $annualRate > 0)
                ? round($runningUtilized * $annualRate / 365 / 100, 4)
                : 0.0;

            $rowInterest = round($dailyInterest * $days, 2);
            $cumulativeInterest = round($cumulativeInterest + $rowInterest, 2);
            $totalPeriodInterest = round($totalPeriodInterest + $rowInterest, 2);

            $calculatedRows->push([
                'id' => $txn->id,
                'is_opening' => false,
                'date' => $currentTxnDate,
                'transaction_no' => $txn->transaction_no ?: "TXN-{$txn->id}",
                'description' => $txn->description,
                'transaction_type' => $txn->transaction_type,
                'debit' => $txnDebit,
                'credit' => $txnCredit,
                'running_balance' => $runningUtilized,
                'od_utilized' => $runningUtilized,
                'od_limit' => $odLimit,
                'available_od' => $availableOd,
                'is_exceeded' => $isRowExceeded,
                'interest_rate' => $annualRate,
                'days' => $days,
                'daily_interest' => $dailyInterest,
                'row_interest' => $rowInterest,
                'cumulative_interest' => $cumulativeInterest,
                'created_by' => $txn->creator?->name,
            ]);
        }

        // Current net utilization across all time
        $currentUtilized = $odAccount->getCurrentUtilization();
        $currentAvailableOd = $odAccount->getAvailableLimit();
        $isExceeded = $odAccount->isExceeded();
        $exceededAmount = $odAccount->getExceededAmount();
        $utilizationPct = $odAccount->getUtilizationPercentage();

        // 4. Apply optional search and transaction type filters on display rows
        $filteredRows = $calculatedRows;
        if (filled($search)) {
            $q = mb_strtolower(trim($search));
            $filteredRows = $filteredRows->filter(function ($row) use ($q) {
                return str_contains(mb_strtolower($row['transaction_no'] ?? ''), $q)
                    || str_contains(mb_strtolower($row['description'] ?? ''), $q)
                    || str_contains(mb_strtolower($row['transaction_type'] ?? ''), $q);
            })->values();
        }

        if (filled($typeFilter)) {
            $filteredRows = $filteredRows->filter(function ($row) use ($typeFilter) {
                return ($row['transaction_type'] ?? '') === $typeFilter;
            })->values();
        }

        // 5. Build newest-first display rows with sequential serial numbers
        $displayRows = $filteredRows->filter(fn ($r) => ! ($r['is_opening'] ?? false))
            ->sort(function ($a, $b) {
                $dateA = $a['date'] instanceof Carbon ? $a['date']->format('Y-m-d') : (string) $a['date'];
                $dateB = $b['date'] instanceof Carbon ? $b['date']->format('Y-m-d') : (string) $b['date'];
                if ($dateA !== $dateB) {
                    return strcmp($dateB, $dateA);
                }
                return ($b['id'] ?? 0) <=> ($a['id'] ?? 0);
            })
            ->values()
            ->map(function ($row, $idx) {
                $row['sr_no'] = $idx + 1;
                return $row;
            });

        $estimatedAnnualInterest = round(($odLimit * $annualRate) / 100, 2);
        $estimatedMonthlyInterest = round($estimatedAnnualInterest / 12, 2);

        return [
            'odAccount' => $odAccount,
            'fromDate' => $from?->toDateString(),
            'toDate' => $to?->toDateString(),
            'openingUtilized' => $openingUtilized,
            'currentUtilized' => $currentUtilized,
            'odLimit' => $odLimit,
            'availableOd' => $currentAvailableOd,
            'utilizationPct' => $utilizationPct,
            'isExceeded' => $isExceeded,
            'exceededAmount' => $exceededAmount,
            'interestRate' => $annualRate,
            'estimatedAnnualInterest' => $estimatedAnnualInterest,
            'estimatedMonthlyInterest' => $estimatedMonthlyInterest,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'totalPeriodInterest' => $totalPeriodInterest,
            'rows' => $filteredRows,
            'displayRows' => $displayRows,
        ];
    }
}

