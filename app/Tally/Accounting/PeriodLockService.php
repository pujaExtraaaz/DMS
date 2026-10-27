<?php

namespace Tally\Accounting;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Tally\Models\AccountingPeriod;
use Tally\Models\Company;
use Tally\Models\FinancialYear;

class PeriodLockService
{
    public function ensureMonthlyPeriods(Company $company, FinancialYear $year): void
    {
        $cursor = $year->start_date->copy()->startOfMonth();
        $end = $year->end_date->copy();

        while ($cursor->lte($end)) {
            $periodStart = $cursor->copy()->max($year->start_date);
            $periodEnd = $cursor->copy()->endOfMonth()->min($year->end_date);

            AccountingPeriod::query()->firstOrCreate(
                [
                    'company_id' => $company->id,
                    'financial_year_id' => $year->id,
                    'start_date' => $periodStart->toDateString(),
                ],
                [
                    'name' => $cursor->format('M Y'),
                    'end_date' => $periodEnd->toDateString(),
                ],
            );

            $cursor->addMonth()->startOfMonth();
        }
    }

    public function assertDateOpen(Company $company, string $date, ?FinancialYear $year = null): void
    {
        if ($company->books_beginning_from && $date < $company->books_beginning_from->toDateString()) {
            throw ValidationException::withMessages([
                'voucher_date' => 'Books begin from '.$company->books_beginning_from->format('d M Y').'.',
            ]);
        }

        if ($year?->is_closed && $company->locksClosedYear()) {
            throw ValidationException::withMessages([
                'voucher_date' => 'Financial year '.$year->name.' is closed.',
            ]);
        }

        $period = AccountingPeriod::query()
            ->where('company_id', $company->id)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->whereNotNull('locked_at')
            ->first();

        if ($period) {
            throw ValidationException::withMessages([
                'voucher_date' => 'Period '.$period->name.' is locked.',
            ]);
        }
    }

    public function lock(AccountingPeriod $period, User $user): AccountingPeriod
    {
        $period->update([
            'locked_at' => now(),
            'locked_by' => $user->id,
        ]);

        return $period->fresh();
    }

    public function unlock(AccountingPeriod $period): AccountingPeriod
    {
        $period->update([
            'locked_at' => null,
            'locked_by' => null,
        ]);

        return $period->fresh();
    }

    public function lockThrough(Company $company, FinancialYear $year, string $throughDate, User $user): int
    {
        $this->ensureMonthlyPeriods($company, $year);

        return AccountingPeriod::query()
            ->where('company_id', $company->id)
            ->where('financial_year_id', $year->id)
            ->whereDate('end_date', '<=', $throughDate)
            ->whereNull('locked_at')
            ->update([
                'locked_at' => now(),
                'locked_by' => $user->id,
                'updated_at' => now(),
            ]);
    }

    /**
     * @return list<AccountingPeriod>
     */
    public function forYear(Company $company, FinancialYear $year): array
    {
        $this->ensureMonthlyPeriods($company, $year);

        return AccountingPeriod::query()
            ->where('company_id', $company->id)
            ->where('financial_year_id', $year->id)
            ->orderBy('start_date')
            ->get()
            ->all();
    }
}
