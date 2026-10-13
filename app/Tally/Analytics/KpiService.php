<?php

namespace Tally\Analytics;

use Carbon\Carbon;
use Tally\Accounting\Money;
use Tally\Models\Bill;
use Tally\Models\Company;
use Tally\Models\KpiSnapshot;
use Tally\Reporting\OutstandingReport;

class KpiService
{
    public function __construct(
        private readonly OutstandingReport $outstanding,
    ) {}

    public function snapshot(Company $company, string $asOf, int $days = 365): KpiSnapshot
    {
        $from = Carbon::parse($asOf)->subDays($days)->toDateString();

        $ar = Money::cents($this->outstanding->receivables($company, $asOf)['total'] ?? '0.00');
        $ap = Money::cents($this->outstanding->payables($company, $asOf)['total'] ?? '0.00');

        $sales = Money::cents((string) Bill::query()
            ->where('company_id', $company->id)
            ->where('side', 'debit')
            ->whereDate('bill_date', '>=', $from)
            ->whereDate('bill_date', '<=', $asOf)
            ->sum('original_amount'));

        $purchases = Money::cents((string) Bill::query()
            ->where('company_id', $company->id)
            ->where('side', 'credit')
            ->whereDate('bill_date', '>=', $from)
            ->whereDate('bill_date', '<=', $asOf)
            ->sum('original_amount'));

        $dso = $sales > 0 ? round(($ar / $sales) * $days, 2) : null;
        $dpo = $purchases > 0 ? round(($ap / $purchases) * $days, 2) : null;

        return KpiSnapshot::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'as_of_date' => $asOf,
            ],
            [
                'dso' => $dso,
                'dpo' => $dpo,
                'working_capital' => Money::format($ar - $ap),
                'extras_json' => [
                    'ar' => Money::format($ar),
                    'ap' => Money::format($ap),
                    'sales' => Money::format($sales),
                    'purchases' => Money::format($purchases),
                    'window_days' => $days,
                ],
            ]
        );
    }
}
