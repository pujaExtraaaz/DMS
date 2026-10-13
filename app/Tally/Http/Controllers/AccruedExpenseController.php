<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Models\AccruedExpense;
use Tally\Payables\AccruedExpenseService;

class AccruedExpenseController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', ['title' => 'Accrued expenses', 'message' => 'Select a company first.']);
        }

        return view('tally::accrued-expenses.index', [
            'company' => $company,
            'expenses' => AccruedExpense::query()
                ->with(['expenseLedger', 'accrualLedger'])
                ->where('company_id', $company->id)
                ->latest('accrual_date')
                ->paginate(25),
            'ledgers' => $company->ledgers()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, AccruedExpenseService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'accrual_date' => ['required', 'date'],
            'expense_ledger_id' => ['required', 'integer'],
            'accrual_ledger_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'goods_receipt_id' => ['nullable', 'integer'],
            'is_uninvoiced_receipt' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(['open', 'reversed', 'closed'])],
            'post' => ['nullable'],
        ]);

        $expense = $service->save($company, $data + [
            'branch_id' => $context->branchId(),
            'is_uninvoiced_receipt' => $request->boolean('is_uninvoiced_receipt'),
        ]);

        if ($request->boolean('post') || (string) $request->input('post') === '1') {
            $service->post($expense, $request->user());
        }

        return back()->with('status', 'Accrued expense saved.');
    }

    public function post(Request $request, WorkspaceContext $context, AccruedExpense $accruedExpense, AccruedExpenseService $service): RedirectResponse
    {
        abort_unless($context->company() && $accruedExpense->company_id === $context->company()->id, 404);

        $service->post($accruedExpense, $request->user());

        return back()->with('status', 'Accrued expense posted.');
    }
}
