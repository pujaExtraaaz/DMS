<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Models\PrepaidExpense;
use Tally\Payables\PrepaidExpenseService;

class PrepaidExpenseController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', ['title' => 'Prepaid expenses', 'message' => 'Select a company first.']);
        }

        return view('tally::prepaid-expenses.index', [
            'company' => $company,
            'expenses' => PrepaidExpense::query()
                ->with(['prepaidLedger', 'expenseLedger'])
                ->where('company_id', $company->id)
                ->latest('id')
                ->paginate(25),
            'ledgers' => $company->ledgers()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, PrepaidExpenseService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'prepaid_ledger_id' => ['required', 'integer'],
            'expense_ledger_id' => ['required', 'integer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'frequency' => ['nullable', Rule::in(['monthly', 'quarterly', 'yearly'])],
            'status' => ['nullable', Rule::in(['active', 'closed'])],
        ]);

        $service->save($company, $data + [
            'branch_id' => $context->branchId(),
        ]);

        return back()->with('status', 'Prepaid expense saved.');
    }

    public function recognize(Request $request, WorkspaceContext $context, PrepaidExpenseService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'as_on' => ['nullable', 'date'],
        ]);

        $done = $service->recognizeDue($data['as_on'] ?? null, $request->user());

        return back()->with('status', count($done) === 0
            ? 'No prepaid expenses recognized.'
            : count($done).' prepaid expense(s) recognized.');
    }
}
