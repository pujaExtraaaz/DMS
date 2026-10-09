<?php

namespace Tally\Http\Controllers;

use Tally\Accounting\VoucherStatus;
use Tally\Context\WorkspaceContext;
use Tally\Http\Requests\StockTransactionRequest;
use Tally\Inventory\StockTransactionService;
use Tally\Inventory\StockTransactionType;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\StockTransaction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StockTransactionController extends Controller
{
    public function index(Request $request, WorkspaceContext $context): View
    {
        $type = $this->type($request);
        $scope = $this->scope($context, $type);

        if ($scope instanceof View) {
            return $scope;
        }

        [$company, $branch, $year] = $scope;
        $search = trim($request->string('q')->toString());
        $like = '%'.addcslashes($search, '%_\\').'%';

        $transactions = StockTransaction::query()
            ->with(['branch'])
            ->where('company_id', $company->id)
            ->where('financial_year_id', $year->id)
            ->where('branch_id', $branch->id)
            ->where('type', $type)
            ->when($search !== '', function ($query) use ($like) {
                $query->where(function ($query) use ($like) {
                    $query->where('number', 'like', $like)->orWhere('narration', 'like', $like);
                });
            })
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('tally::stock.index', [
            'type' => $type,
            'company' => $company,
            'branch' => $branch,
            'year' => $year,
            'transactions' => $transactions,
            'filters' => ['q' => $search],
        ]);
    }

    public function create(Request $request, WorkspaceContext $context): View
    {
        $type = $this->type($request);
        $scope = $this->scope($context, $type);

        if ($scope instanceof View) {
            return $scope;
        }

        [$company, $branch, $year] = $scope;

        return view('tally::stock.create', [
            'type' => $type,
            'company' => $company,
            'branch' => $branch,
            'year' => $year,
            'products' => $company->products()->where('is_active', true)->orderBy('name')->get(),
            'godowns' => $company->godowns()->where('is_active', true)->orderBy('name')->get(),
            'date' => $this->defaultDate($year),
        ]);
    }

    public function store(StockTransactionRequest $request, WorkspaceContext $context, StockTransactionService $stock): RedirectResponse
    {
        $type = $this->type($request);
        $transaction = $stock->save(
            $context->company(),
            $context->branch(),
            $context->financialYear(),
            $request->user(),
            $type,
            $request->validated(),
        );

        return redirect()
            ->route($type->routeName('show'), $transaction)
            ->with('status', $type->label().' posted.');
    }

    public function show(Request $request, StockTransaction $stockTransaction): View
    {
        $this->guard($request, $stockTransaction);
        $stockTransaction->load(['lines.product', 'lines.godown', 'sourceGodown', 'destinationGodown', 'creator', 'branch']);

        return view('tally::stock.show', [
            'type' => $stockTransaction->type,
            'transaction' => $stockTransaction,
        ]);
    }

    public function cancel(Request $request, StockTransactionService $stock, StockTransaction $stockTransaction): RedirectResponse
    {
        $this->guard($request, $stockTransaction);
        $stock->cancel($stockTransaction);

        return redirect()
            ->route($stockTransaction->type->routeName('show'), $stockTransaction)
            ->with('status', $stockTransaction->type->label().' cancelled.');
    }

    private function type(Request $request): StockTransactionType
    {
        $name = (string) $request->route()?->getName();

        foreach (StockTransactionType::cases() as $type) {
            if (str_starts_with($name, 'stock.'.$type->value.'.')) {
                return $type;
            }
        }

        abort(404);
    }

    private function guard(Request $request, StockTransaction $transaction): void
    {
        abort_unless($transaction->type === $this->type($request), 404);
        abort_unless($transaction->status !== VoucherStatus::Draft, 404);
    }

    /**
     * @return array{0: Company, 1: Branch, 2: FinancialYear}|View
     */
    private function scope(WorkspaceContext $context, StockTransactionType $type): array|View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => $type->label(),
                'message' => 'Select or create a company before recording '.$type->label().'.',
            ]);
        }

        if (! $context->branch() || ! $context->financialYear()) {
            return view('tally::workspace.needs-company', [
                'title' => $type->label(),
                'message' => 'Select a branch and a financial year before recording '.$type->label().'.',
            ]);
        }

        return [$company, $context->branch(), $context->financialYear()];
    }

    private function defaultDate(FinancialYear $year): string
    {
        $today = now()->toDateString();

        if ($today >= $year->start_date->toDateString() && $today <= $year->end_date->toDateString()) {
            return $today;
        }

        return $year->start_date->toDateString();
    }
}
