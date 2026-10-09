<?php

namespace Tally\Http\Controllers;

use Tally\Accounting\CostCentreReport;
use Tally\Accounting\InterestCalculator;
use Tally\Context\WorkspaceContext;
use Tally\Models\Bill;
use Tally\Accounting\Money;
use Tally\Reporting\FinancialReports;
use Tally\Reporting\LedgerBalances;
use Tally\Reporting\OutstandingReport;
use Tally\Tax\GstReport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AccountingReportController extends Controller
{
    public function gst(Request $request, WorkspaceContext $context, GstReport $report): View
    {
        $scope = $this->scope($context, 'GST summary');

        if ($scope instanceof View) {
            return $scope;
        }

        [$company, $year, $from, $to, $branchId] = $scope;
        $search = trim($request->string('q')->toString());

        return view('tally::reports.gst', [
            'company' => $company,
            'year' => $year,
            'filters' => ['from' => $from, 'to' => $to, 'branch_id' => $branchId, 'q' => $search],
            'branches' => $company->branches()->where('is_active', true)->orderBy('name')->get(),
            'report' => $report->build($company, $from, $to, $branchId, $search, max(1, $request->integer('page', 1))),
        ]);
    }

    public function dayBook(Request $request, WorkspaceContext $context, FinancialReports $reports): View
    {
        $scope = $this->scope($context, 'Day book');

        if ($scope instanceof View) {
            return $scope;
        }

        [$company, $year, $from, $to, $branchId] = $scope;
        $search = trim($request->string('q')->toString());

        return view('tally::reports.day-book', [
            'company' => $company,
            'year' => $year,
            'filters' => ['from' => $from, 'to' => $to, 'branch_id' => $branchId, 'q' => $search],
            'branches' => $company->branches()->where('is_active', true)->orderBy('name')->get(),
            'report' => $reports->dayBook($company, $branchId, $year, $from, $to, $search),
        ]);
    }

    public function ledger(Request $request, WorkspaceContext $context, FinancialReports $reports): View
    {
        $scope = $this->scope($context, 'Ledger');

        if ($scope instanceof View) {
            return $scope;
        }

        [$company, $year, $from, $to, $branchId] = $scope;
        $ledgerId = $request->integer('ledger_id') ?: null;

        if ($ledgerId && ! $company->ledgers()->whereKey($ledgerId)->exists()) {
            $ledgerId = null;
        }

        return view('tally::reports.ledger', [
            'company' => $company,
            'year' => $year,
            'filters' => ['from' => $from, 'to' => $to, 'branch_id' => $branchId, 'ledger_id' => $ledgerId],
            'branches' => $company->branches()->where('is_active', true)->orderBy('name')->get(),
            'ledgers' => $company->ledgers()->orderBy('name')->get(),
            'report' => $reports->ledger($company, $branchId, $year, $ledgerId, $from, $to),
        ]);
    }

    public function trialBalance(Request $request, WorkspaceContext $context, FinancialReports $reports): View
    {
        $scope = $this->scope($context, 'Trial balance');

        if ($scope instanceof View) {
            return $scope;
        }

        [$company, $year, $from, $to, $branchId] = $scope;

        return view('tally::reports.trial-balance', [
            'company' => $company,
            'year' => $year,
            'filters' => ['from' => $from, 'to' => $to, 'branch_id' => $branchId],
            'branches' => $company->branches()->where('is_active', true)->orderBy('name')->get(),
            'report' => $reports->trialBalance($company, $branchId, $year, $to),
        ]);
    }

    public function profitAndLoss(Request $request, WorkspaceContext $context, FinancialReports $reports): View
    {
        $scope = $this->scope($context, 'Profit and loss');

        if ($scope instanceof View) {
            return $scope;
        }

        [$company, $year, $from, $to, $branchId] = $scope;

        return view('tally::reports.profit-and-loss', [
            'company' => $company,
            'year' => $year,
            'filters' => ['from' => $from, 'to' => $to, 'branch_id' => $branchId],
            'branches' => $company->branches()->where('is_active', true)->orderBy('name')->get(),
            'report' => $reports->profitAndLoss($company, $branchId, $year, $to),
        ]);
    }

    public function ratioAnalysis(Request $request, WorkspaceContext $context, FinancialReports $reports, LedgerBalances $balances): View
    {
        $scope = $this->scope($context, 'Ratio analysis');

        if ($scope instanceof View) {
            return $scope;
        }

        [$company, $year, $from, $to, $branchId] = $scope;
        $profit = $reports->profitAndLoss($company, $branchId, $year, $to);
        $sales = $this->moneyCents($profit['direct_income']);
        $purchases = $this->moneyCents($profit['direct_expense']) + $this->moneyCents($profit['cogs']);
        $gross = $this->signedCents($profit['gross_profit'], $profit['gross_side'] === 'profit');
        $net = $this->signedCents($profit['net_profit'], $profit['net_side'] === 'profit');
        $buckets = [
            'cash' => 0,
            'bank' => 0,
            'debtors' => 0,
            'creditors' => 0,
            'stock' => 0,
            'current_assets' => 0,
            'current_liabilities' => 0,
            'loans' => 0,
            'capital' => 0,
        ];

        foreach ($balances->asOn($company, $branchId, $year, $to) as $row) {
            $asset = $row['debit'] - $row['credit'];
            $liability = $row['credit'] - $row['debit'];
            $codes = $row['codes'];

            if (in_array('CASH', $codes, true)) {
                $buckets['cash'] += $asset;
            }

            if (in_array('BANK', $codes, true)) {
                $buckets['bank'] += $asset;
            }

            if (in_array('DEBTORS', $codes, true)) {
                $buckets['debtors'] += $asset;
            }

            if (in_array('CREDITORS', $codes, true)) {
                $buckets['creditors'] += $liability;
            }

            if (in_array('STOCK', $codes, true) || in_array($row['ledger']->code, ['RAW', 'FG'], true)) {
                $buckets['stock'] += $asset;
            }

            if (in_array('CURRENT_ASSETS', $codes, true)) {
                $buckets['current_assets'] += $asset;
            }

            if (in_array('CURRENT_LIABILITIES', $codes, true)) {
                $buckets['current_liabilities'] += $liability;
            }

            if (in_array('LOANS', $codes, true)) {
                $buckets['loans'] += $liability;
            }

            if (in_array('CAPITAL', $codes, true)) {
                $buckets['capital'] += $liability;
            }
        }

        $workingCapital = $buckets['current_assets'] - $buckets['current_liabilities'];
        $figure = fn (int $amount, string $side, string $href): array => $amount !== 0
            ? [Money::format(abs($amount)).' '.$side, $href]
            : ['—', $href];
        $groups = [
            'Working Capital' => $figure($workingCapital, $workingCapital < 0 ? 'Cr' : 'Dr', tally_route('reports.balance-sheet')),
            'Cash-in-Hand' => $figure($buckets['cash'], $buckets['cash'] > 0 ? 'Dr' : 'Cr', tally_route('reports.ledger')),
            'Bank Accounts' => $figure($buckets['bank'], $buckets['bank'] > 0 ? 'Dr' : 'Cr', tally_route('banking.activities')),
            'Sundry Debtors' => $figure($buckets['debtors'], 'Dr', tally_route('reports.outstanding')),
            'Sundry Creditors' => $figure($buckets['creditors'], 'Cr', tally_route('reports.outstanding')),
            'Sales Accounts' => $figure($sales, 'Cr', tally_route('reports.profit-and-loss')),
            'Purchase Accounts' => $figure($purchases, 'Dr', tally_route('reports.profit-and-loss')),
            'Stock-in-Hand' => $figure($buckets['stock'], $buckets['stock'] > 0 ? 'Dr' : 'Cr', tally_route('reports.balance-sheet')),
            ($net >= 0 ? 'Nett Profit' : 'Nett Loss') => $figure($net, $net >= 0 ? 'Cr' : 'Dr', tally_route('reports.profit-and-loss')),
        ];
        $quick = $buckets['current_assets'] - $buckets['stock'];
        $days = max(1, (int) \Illuminate\Support\Carbon::parse($from)->diffInDays(\Illuminate\Support\Carbon::parse($to)) + 1);
        $ratioRow = fn (?string $value, string $note, string $href): array => [$value ?? '—', $note, $href];
        $ratios = [
            'Current Ratio' => $ratioRow($buckets['current_liabilities'] !== 0 ? $this->ratio($buckets['current_assets'], $buckets['current_liabilities']) : null, '(Current Assets : Current Liabilities)', tally_route('reports.balance-sheet')),
            'Quick Ratio' => $ratioRow($buckets['current_liabilities'] !== 0 ? $this->ratio($quick, $buckets['current_liabilities']) : null, '(Current Assets − Stock-in-hand : Current Liabilities)', tally_route('reports.balance-sheet')),
            'Debt/Equity Ratio' => $ratioRow($buckets['capital'] !== 0 && $buckets['loans'] !== 0 ? $this->ratio($buckets['loans'], $buckets['capital']) : null, '(Loans (Liability) : Capital Account)', tally_route('reports.balance-sheet')),
            'Gross Profit %' => $ratioRow($sales !== 0 ? $this->percent($gross, $sales) : null, '(Gross Profit / Sales Accounts)', tally_route('reports.profit-and-loss')),
            'Net Profit %' => $ratioRow($sales !== 0 ? $this->percent($net, $sales) : null, '(Nett Profit / Sales Accounts)', tally_route('reports.profit-and-loss')),
            'Operating Cost %' => $ratioRow($sales !== 0 && $purchases !== 0 ? $this->percent($purchases, $sales) : null, '(Purchase Accounts / Sales Accounts)', tally_route('reports.profit-and-loss')),
            'Receivable Turnover in days' => $ratioRow($sales !== 0 && $buckets['debtors'] > 0 ? number_format(($buckets['debtors'] / $sales) * $days, 2).' days' : null, '(Sundry Debtors / Sales Accounts)', tally_route('reports.outstanding')),
            'Inventory Turnover' => $ratioRow($buckets['stock'] > 0 && $purchases !== 0 ? $this->ratio($purchases, $buckets['stock']) : null, '(Purchase Accounts / Stock-in-Hand)', tally_route('reports.balance-sheet')),
            'Return on Investment %' => $ratioRow(($buckets['capital'] + $net) !== 0 ? $this->percent($net, $buckets['capital'] + $net) : null, '(Nett Profit / Capital Account + Nett Profit)', tally_route('reports.profit-and-loss')),
            'Return on Wkg. Capital %' => $ratioRow($workingCapital !== 0 ? $this->percent($net, $workingCapital) : null, '(Nett Profit / Working Capital)', tally_route('reports.balance-sheet')),
        ];

        return view('tally::reports.ratio-analysis', [
            'company' => $company,
            'year' => $year,
            'filters' => ['from' => $from, 'to' => $to, 'branch_id' => $branchId],
            'groups' => $groups,
            'ratios' => $ratios,
        ]);
    }

    public function balanceSheet(Request $request, WorkspaceContext $context, FinancialReports $reports): View
    {
        $scope = $this->scope($context, 'Balance sheet');

        if ($scope instanceof View) {
            return $scope;
        }

        [$company, $year, $from, $to, $branchId] = $scope;

        return view('tally::reports.balance-sheet', [
            'company' => $company,
            'year' => $year,
            'filters' => ['from' => $from, 'to' => $to, 'branch_id' => $branchId],
            'branches' => $company->branches()->where('is_active', true)->orderBy('name')->get(),
            'report' => $reports->balanceSheet($company, $branchId, $year, $to),
            'group' => $request->string('group')->toString(),
        ]);
    }

    public function outstanding(Request $request, WorkspaceContext $context, OutstandingReport $report): View
    {
        $scope = $this->scope($context, 'Outstanding');

        if ($scope instanceof View) {
            return $scope;
        }

        [$company, $year, $from, $to, $branchId] = $scope;
        $ledgerId = $request->integer('ledger_id') ?: null;

        return view('tally::reports.outstanding', [
            'company' => $company,
            'year' => $year,
            'filters' => ['from' => $from, 'to' => $to, 'branch_id' => $branchId, 'ledger_id' => $ledgerId],
            'branches' => $company->branches()->where('is_active', true)->orderBy('name')->get(),
            'receivables' => $report->receivables($company, $to, $branchId, $ledgerId),
            'payables' => $report->payables($company, $to, $branchId, $ledgerId),
        ]);
    }

    public function costCentres(Request $request, WorkspaceContext $context, CostCentreReport $report): View
    {
        $scope = $this->scope($context, 'Cost centres');

        if ($scope instanceof View) {
            return $scope;
        }

        [$company, $year, $from, $to, $branchId] = $scope;
        $categoryId = $request->integer('cost_category_id') ?: null;

        return view('tally::reports.cost-centres', [
            'company' => $company,
            'year' => $year,
            'filters' => ['from' => $from, 'to' => $to, 'branch_id' => $branchId, 'cost_category_id' => $categoryId],
            'branches' => $company->branches()->where('is_active', true)->orderBy('name')->get(),
            'categories' => $company->costCategories()->orderBy('name')->get(),
            'rows' => $report->rows($company, $from, $to, $branchId, $categoryId),
        ]);
    }

    public function interest(Request $request, WorkspaceContext $context, OutstandingReport $outstanding, InterestCalculator $interest): View
    {
        $scope = $this->scope($context, 'Interest');

        if ($scope instanceof View) {
            return $scope;
        }

        [$company, $year, $from, $to, $branchId] = $scope;
        $rate = trim((string) $request->input('rate', '18'));
        $rows = [];
        $sources = [
            'receivable' => $outstanding->receivables($company, $to, $branchId),
            'payable' => $outstanding->payables($company, $to, $branchId),
        ];
        $billIds = [];

        foreach ($sources as $report) {
            foreach ($report['rows'] as $row) {
                $billIds[] = $row['bill_id'];
            }
        }

        $bills = Bill::query()
            ->with('entry')
            ->where('company_id', $company->id)
            ->whereIn('id', $billIds === [] ? [0] : $billIds)
            ->get()
            ->keyBy('id');

        foreach ($sources as $kind => $report) {
            foreach ($report['rows'] as $row) {
                $bill = $bills->get($row['bill_id']);
                $due = $bill?->due_date?->toDateString() ?? $to;
                $calculated = $interest->onOutstanding($row['outstanding'], $rate === '' ? '0' : $rate, $due, $to);
                $rows[] = $row + [
                    'kind' => $kind,
                    'days' => $calculated['days'],
                    'interest' => $calculated['interest'],
                    'voucher_url' => $bill?->entry?->voucher_id ? tally_route('vouchers.show', $bill->entry->voucher_id) : null,
                ];
            }
        }

        return view('tally::reports.interest', [
            'company' => $company,
            'year' => $year,
            'filters' => ['from' => $from, 'to' => $to, 'branch_id' => $branchId, 'rate' => $rate],
            'branches' => $company->branches()->where('is_active', true)->orderBy('name')->get(),
            'rows' => $rows,
        ]);
    }

    /**
     * @return array{0: \Tally\Models\Company, 1: \Tally\Models\FinancialYear, 2: string, 3: string, 4: ?int}|View
     */
    private function scope(WorkspaceContext $context, string $title): array|View
    {
        if (! $context->company() || ! $context->financialYear()) {
            return view('tally::workspace.needs-company', [
                'title' => $title,
                'message' => 'Select a company and financial year before opening this report.',
            ]);
        }

        $year = $context->financialYear();
        [$periodFrom, $periodTo] = app(\Tally\Context\WorkingCalendar::class)->period($context->company(), request()->user(), $year);
        $from = $this->date(request()->input('from')) ?? $periodFrom;
        $to = $this->date(request()->input('to')) ?? $periodTo;
        $branch = request()->input('branch_id');
        $branchId = $branch === 'all' || $branch === null || $branch === '' ? $context->branch()?->id : (int) $branch;

        if (request()->input('branch_id') === 'all') {
            $branchId = null;
        }

        return [$context->company(), $year, $from, $to, $branchId];
    }

    private function date(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' && strtotime($value) ? date('Y-m-d', strtotime($value)) : null;
    }

    /**
     * @param  list<array{amount: string}>  $lines
     */
    private function moneyCents(array $lines): int
    {
        $total = 0;

        foreach ($lines as $line) {
            $total += $this->plainCents($line['amount']);
        }

        return $total;
    }

    private function signedCents(string $amount, bool $positive): int
    {
        $cents = $this->plainCents($amount);

        return $positive ? $cents : -$cents;
    }

    private function plainCents(string $amount): int
    {
        $clean = str_replace(',', '', $amount);

        return (int) round(((float) $clean) * 100);
    }

    private function ratio(int $left, int $right): string
    {
        if ($right === 0) {
            return '';
        }

        return number_format($left / $right, 2).' : 1';
    }

    private function percent(int $part, int $whole): string
    {
        if ($whole === 0) {
            return '—';
        }

        return number_format(($part / $whole) * 100, 2).'%';
    }
}
