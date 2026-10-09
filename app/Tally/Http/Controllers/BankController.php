<?php

namespace Tally\Http\Controllers;

use Tally\Accounting\Money;
use Tally\Banking\BankBook;
use Tally\Banking\BankReconciliationService;
use Tally\Context\WorkspaceContext;
use Tally\Reporting\LedgerBalances;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\VoucherEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BankController extends Controller
{
    public function menu(): View
    {
        return view('tally::banking.menu');
    }

    public function activities(Request $request, WorkspaceContext $context, BankBook $book, LedgerBalances $balances): View
    {
        $scope = $this->scope($context, 'Banking Activities');

        if ($scope instanceof View) {
            return $scope;
        }

        [$company, $branch, $year] = $scope;
        $ledgers = $book->bankLedgers($company);
        $asOn = $this->defaultTo($year);
        $figures = [];

        foreach ($balances->asOn($company, $branch->id, $year, $asOn) as $row) {
            $signed = $row['debit'] - $row['credit'];
            $figures[$row['ledger']->id] = $signed === 0 ? '' : Money::format(abs($signed)).($signed > 0 ? ' Dr' : ' Cr');
        }

        $selected = $ledgers->first(fn ($ledger) => (string) $ledger->id === (string) $request->input('ledger_id'));

        return view('tally::banking.activities', [
            'company' => $company,
            'ledgers' => $ledgers,
            'figures' => $figures,
            'selected' => $selected,
        ]);
    }

    public function accounts(WorkspaceContext $context, BankBook $book): View
    {
        $scope = $this->scope($context, 'Bank accounts');

        if ($scope instanceof View) {
            return $scope;
        }

        [$company] = $scope;

        return view('tally::banking.accounts', [
            'company' => $company,
            'ledgers' => $book->bankLedgers($company),
        ]);
    }

    public function transactions(Request $request, WorkspaceContext $context, BankBook $book): View
    {
        return $this->listing($request, $context, $book, 'banking.transactions', 'Bank transactions');
    }

    public function reconciliation(Request $request, WorkspaceContext $context, BankBook $book): View
    {
        return $this->listing($request, $context, $book, 'banking.reconciliation', 'Bank reconciliation');
    }

    public function reconcile(Request $request, WorkspaceContext $context, BankReconciliationService $service, VoucherEntry $voucherEntry): RedirectResponse
    {
        $year = $context->financialYear();
        $validated = $request->validate([
            'reference' => ['nullable', 'string', 'max:50'],
            'transaction_date' => ['required', 'date'],
            'bank_amount' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999999.99'],
            'reconciliation_date' => ['required', 'date'],
        ]);
        $service->reconcile($voucherEntry, $request->user(), $year, $validated);

        return back()->with('status', 'Transaction reconciled.');
    }

    public function unreconcile(Request $request, BankReconciliationService $service, VoucherEntry $voucherEntry): RedirectResponse
    {
        $service->unreconcile($voucherEntry, $request->user());

        return back()->with('status', 'Transaction marked unreconciled.');
    }

    private function listing(Request $request, WorkspaceContext $context, BankBook $book, string $view, string $title): View
    {
        $scope = $this->scope($context, $title);

        if ($scope instanceof View) {
            return $scope;
        }

        [$company, $branch, $year] = $scope;
        $filters = $this->filters($request, $company, $branch, $year);
        $ledgerId = $filters['ledger_id'] !== '' ? (int) $filters['ledger_id'] : null;

        return view($view, [
            'company' => $company,
            'year' => $year,
            'branches' => $company->branches()->where('is_active', true)->orderBy('name')->get(),
            'ledgers' => $book->bankLedgers($company),
            'rows' => $book->lines($company, $year, $filters['branch_id'], $ledgerId, $filters['from'], $filters['to'], $filters['q']),
            'filters' => $filters,
        ]);
    }

    /**
     * @return array{0: Company, 1: Branch, 2: FinancialYear}|View
     */
    private function scope(WorkspaceContext $context, string $title): array|View
    {
        if (! $context->company() || ! $context->branch() || ! $context->financialYear()) {
            return view('tally::workspace.needs-company', [
                'title' => $title,
                'message' => 'Select a company, branch, and financial year before using banking.',
            ]);
        }

        return [$context->company(), $context->branch(), $context->financialYear()];
    }

    /**
     * @return array{q: string, from: string, to: string, branch_id: ?int, ledger_id: string}
     */
    private function filters(Request $request, Company $company, Branch $branch, FinancialYear $year): array
    {
        $start = $year->start_date->toDateString();
        $end = $year->end_date->toDateString();
        $from = $this->date($request->input('from')) ?? $start;
        $to = $this->date($request->input('to')) ?? $this->defaultTo($year);
        $from = min(max($from, $start), $end);
        $to = min(max($to, $start), $end);

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $branchId = (string) $request->input('branch_id', $branch->id);
        $resolved = null;

        if ($branchId !== 'all') {
            $resolved = $company->branches()->whereKey($branchId)->exists() ? (int) $branchId : $branch->id;
        }

        $ledgerId = (string) $request->input('ledger_id', '');

        if ($ledgerId !== '' && ! $company->ledgers()->whereKey($ledgerId)->exists()) {
            $ledgerId = '';
        }

        return [
            'q' => trim($request->string('q')->toString()),
            'from' => $from,
            'to' => $to,
            'branch_id' => $resolved,
            'ledger_id' => $ledgerId,
        ];
    }

    private function defaultTo(FinancialYear $year): string
    {
        $today = now()->toDateString();
        $start = $year->start_date->toDateString();
        $end = $year->end_date->toDateString();

        return ($today >= $start && $today <= $end) ? $today : $end;
    }

    private function date(mixed $value): ?string
    {
        $value = trim((string) $value);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }
}
