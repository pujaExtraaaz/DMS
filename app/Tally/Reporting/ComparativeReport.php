<?php

namespace Tally\Reporting;

use Carbon\Carbon;
use Tally\Accounting\Money;
use Tally\Models\Company;
use Tally\Models\FinancialYear;

class ComparativeReport
{
    public function __construct(
        private readonly FinancialReports $reports,
    ) {}

    /**
     * YoY: current vs prior period totals for P&L groups.
     *
     * @return array{
     *   current: array{from: string, to: string, groups: array<string, string>, net: string},
     *   prior: array{from: string, to: string, groups: array<string, string>, net: string},
     *   variance: array<string, string>
     * }
     */
    public function yearOverYear(Company $company, string $from, string $to, ?int $branchId = null): array
    {
        $current = $this->periodTotals($company, $from, $to, $branchId);

        $fromPrior = Carbon::parse($from)->subYear()->toDateString();
        $toPrior = Carbon::parse($to)->subYear()->toDateString();
        $prior = $this->periodTotals($company, $fromPrior, $toPrior, $branchId);

        $variance = [];
        foreach (array_keys($current['groups']) as $key) {
            $cur = Money::cents($current['groups'][$key] ?? '0.00');
            $prev = Money::cents($prior['groups'][$key] ?? '0.00');
            $variance[$key] = Money::format($cur - $prev);
        }

        return [
            'current' => $current,
            'prior' => $prior,
            'variance' => $variance,
        ];
    }

    /**
     * @return array{from: string, to: string, groups: array<string, string>, net: string}
     */
    private function periodTotals(Company $company, string $from, string $to, ?int $branchId): array
    {
        $year = FinancialYear::query()
            ->where('company_id', $company->id)
            ->whereDate('start_date', '<=', $to)
            ->whereDate('end_date', '>=', $from)
            ->first()
            ?? $company->financialYears()->orderByDesc('start_date')->first();

        $groups = [
            'direct_income' => '0.00',
            'direct_expense' => '0.00',
            'indirect_income' => '0.00',
            'indirect_expense' => '0.00',
            'cogs' => '0.00',
        ];
        $net = '0.00';

        if ($year) {
            $pnl = $this->reports->profitAndLoss($company, $branchId, $year, $to);
            $groups = [
                'direct_income' => Money::format($this->sumLines($pnl['direct_income'] ?? [])),
                'direct_expense' => Money::format($this->sumLines($pnl['direct_expense'] ?? [])),
                'indirect_income' => Money::format($this->sumLines($pnl['indirect_income'] ?? [])),
                'indirect_expense' => Money::format($this->sumLines($pnl['indirect_expense'] ?? [])),
                'cogs' => Money::format($this->sumLines($pnl['cogs'] ?? [])),
            ];
            $net = ($pnl['net_side'] ?? 'profit') === 'loss'
                ? '-'.($pnl['net_profit'] ?? '0.00')
                : ($pnl['net_profit'] ?? '0.00');
        }

        return [
            'from' => $from,
            'to' => $to,
            'groups' => $groups,
            'net' => $net,
        ];
    }

    /**
     * @param  list<array{amount: string}>  $lines
     */
    private function sumLines(array $lines): int
    {
        $total = 0;
        foreach ($lines as $line) {
            $total += Money::cents((string) ($line['amount'] ?? 0));
        }

        return $total;
    }
}
