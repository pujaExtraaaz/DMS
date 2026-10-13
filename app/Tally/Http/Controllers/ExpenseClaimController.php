<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Models\ExpenseClaim;
use Tally\Payables\ExpenseClaimService;

class ExpenseClaimController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', ['title' => 'Expense claims', 'message' => 'Select a company first.']);
        }

        return view('tally::expense-claims.index', [
            'company' => $company,
            'claims' => ExpenseClaim::query()
                ->with('employeeLedger')
                ->where('company_id', $company->id)
                ->when($context->financialYear(), fn ($q) => $q->where('financial_year_id', $context->financialYear()->id))
                ->latest('claim_date')
                ->paginate(25),
        ]);
    }

    public function create(WorkspaceContext $context): View
    {
        $company = $context->company();
        $branch = $context->branch();
        $year = $context->financialYear();
        abort_unless($company && $branch && $year, 404);

        return view('tally::expense-claims.form', [
            'company' => $company,
            'claim' => new ExpenseClaim(['claim_date' => now()->toDateString(), 'status' => 'draft', 'is_reimbursement' => true]),
            'ledgers' => $company->ledgers()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, ExpenseClaimService $service): RedirectResponse
    {
        $company = $context->company();
        $branch = $context->branch();
        $year = $context->financialYear();
        abort_unless($company && $branch && $year, 404);

        $data = $request->validate([
            'claim_date' => ['required', 'date'],
            'employee_id' => ['nullable', 'integer'],
            'employee_ledger_id' => ['nullable', 'integer'],
            'category' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', Rule::in(['draft', 'submitted', 'approved', 'paid', 'rejected'])],
            'is_reimbursement' => ['nullable', 'boolean'],
            'narration' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.expense_date' => ['nullable', 'date'],
            'lines.*.category' => ['nullable', 'string', 'max:80'],
            'lines.*.expense_ledger_id' => ['nullable', 'integer'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.amount' => ['required', 'numeric', 'min:0.01'],
            'lines.*.bill_attached' => ['nullable', 'boolean'],
        ]);

        $claim = $service->save($company, $branch, $year, $request->user(), $data);

        return redirect()->to(tally_route('books.tally.expense-claims.show', $claim))
            ->with('status', 'Expense claim saved.');
    }

    public function show(WorkspaceContext $context, ExpenseClaim $expenseClaim): View
    {
        abort_unless($context->company() && $expenseClaim->company_id === $context->company()->id, 404);
        $expenseClaim->load(['lines.expenseLedger', 'employeeLedger', 'accrualVoucher', 'paymentVoucher', 'approver']);

        $company = $context->company();

        return view('tally::expense-claims.show', [
            'company' => $company,
            'claim' => $expenseClaim,
            'bankLedgers' => $company->ledgers()->with('accountGroup')->orderBy('name')->get()->filter(fn ($l) => $l->isCashOrBank()),
        ]);
    }

    public function approve(Request $request, WorkspaceContext $context, ExpenseClaim $expenseClaim, ExpenseClaimService $service): RedirectResponse
    {
        abort_unless($context->company() && $expenseClaim->company_id === $context->company()->id, 404);

        $service->approve($context->company(), $expenseClaim, $request->user());

        return back()->with('status', 'Expense claim approved and accrual posted.');
    }

    public function pay(Request $request, WorkspaceContext $context, ExpenseClaim $expenseClaim, ExpenseClaimService $service): RedirectResponse
    {
        abort_unless($context->company() && $expenseClaim->company_id === $context->company()->id, 404);

        $data = $request->validate([
            'bank_ledger_id' => ['required', 'integer'],
        ]);

        $service->pay($context->company(), $expenseClaim, $request->user(), (int) $data['bank_ledger_id']);

        return back()->with('status', 'Expense claim payment posted.');
    }
}
