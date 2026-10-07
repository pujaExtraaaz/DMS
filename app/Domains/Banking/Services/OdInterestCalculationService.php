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
        string $fromDate,
        string $toDate,
        ?string $search = null,
        ?string $typeFilter = null
    ): array {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->endOfDay();

        $odLimit = (float) $odAccount->od_limit;
        $annualRate = (float) $odAccount->interest_rate;

        // 1. Calculate opening utilized balance prior to fromDate
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

        // 2. Fetch all transactions in the selected date range
        $txnsQuery = BankAccountTransaction::query()
            ->where('company_id', $odAccount->company_id)
            ->where('account_number', $odAccount->account_number)
            ->whereDate('transaction_date', '>=', $from->toDateString())
            ->whereDate('transaction_date', '<=', $to->toDateString())
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

        // If there is an opening balance, create an Opening Position entry if there are subsequent transactions
        // or if opening balance is non-zero
        if ($openingUtilized > 0 && $allPeriodTxns->isNotEmpty()) {
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
                    'debit' => 0.0,
                    'credit' => 0.0,
                    'od_utilized' => $openingUtilized,
                    'available_od' => max(0.0, round($odLimit - $openingUtilized, 2)),
                    'is_exceeded' => $openingUtilized > $odLimit,
                    'interest_rate' => $annualRate,
                    'days' => $openingDays,
                    'daily_interest' => $dailyInt,
                    'row_interest' => $periodInt,
                    'cumulative_interest' => $cumulativeInterest,
                ]);
            }
        } elseif ($openingUtilized > 0 && $allPeriodTxns->isEmpty()) {
            // No transactions in period, opening balance holds for the entire range
            $periodDays = max(1, $from->diffInDays($to) + 1);
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
                'debit' => 0.0,
                'credit' => 0.0,
                'od_utilized' => $openingUtilized,
                'available_od' => max(0.0, round($odLimit - $openingUtilized, 2)),
                'is_exceeded' => $openingUtilized > $odLimit,
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
            $availableOd = max(0.0, round($odLimit - $runningUtilized, 2));
            $isRowExceeded = $runningUtilized > $odLimit;

            $currentTxnDate = Carbon::parse($txn->transaction_date);

            // Determine days utilized for this specific row:
            if ($i + 1 < $count) {
                $nextTxnDate = Carbon::parse($allPeriodTxns[$i + 1]->transaction_date);
                if ($nextTxnDate->greaterThan($currentTxnDate)) {
                    $days = (int) $currentTxnDate->diffInDays($nextTxnDate);
                } else {
                    // Subsequent transaction on the same date:
                    // 0 days for intermediate intraday row to prevent duplicate interest
                    $days = 0;
                }
            } else {
                // Last transaction in the period:
                // Held until the end of the requested period (toDate)
                $toEnd = Carbon::parse($toDate)->startOfDay();
                if ($toEnd->greaterThanOrEqualTo($currentTxnDate)) {
                    $days = (int) $currentTxnDate->diffInDays($toEnd) + 1;
                } else {
                    $days = 1;
                }
            }

            // Daily Interest: Simple Daily = OD Utilized * Rate / 365 / 100
            $dailyInterest = $runningUtilized > 0
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
                'od_utilized' => $runningUtilized,
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

        return [
            'odAccount' => $odAccount,
            'fromDate' => $from->toDateString(),
            'toDate' => $to->toDateString(),
            'openingUtilized' => $openingUtilized,
            'currentUtilized' => $currentUtilized,
            'odLimit' => $odLimit,
            'availableOd' => $currentAvailableOd,
            'utilizationPct' => $utilizationPct,
            'isExceeded' => $isExceeded,
            'exceededAmount' => $exceededAmount,
            'interestRate' => $annualRate,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'totalPeriodInterest' => $totalPeriodInterest,
            'rows' => $filteredRows,
        ];
    }
}

