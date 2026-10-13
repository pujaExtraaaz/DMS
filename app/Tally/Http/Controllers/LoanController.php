<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Loans\LoanService;
use Tally\Models\BankAccount;
use Tally\Models\Ledger;
use Tally\Models\Loan;
use Tally\Models\LoanSchedule;

class LoanController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Loans',
                'message' => 'Select a company first.',
            ]);
        }

        return view('tally::loans.index', [
            'company' => $company,
            'loans' => Loan::query()->where('company_id', $company->id)->latest('disbursement_date')->paginate(25),
            'ledgers' => Ledger::query()->where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get(),
            'banks' => BankAccount::query()->where('company_id', $company->id)->orderBy('bank_name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, LoanService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'loan_number' => ['required', 'string', 'max:40', Rule::unique('acct_loans', 'loan_number')->where('company_id', $company->id)],
            'lender_name' => ['required', 'string', 'max:160'],
            'loan_type' => ['nullable', Rule::in(['term', 'od', 'overdraft', 'lease'])],
            'is_long_term' => ['nullable', 'boolean'],
            'disbursement_date' => ['required', 'date'],
            'principal' => ['required', 'numeric', 'gt:0'],
            'interest_rate' => ['nullable', 'numeric', 'min:0'],
            'tenure_months' => ['required', 'integer', 'min:1', 'max:600'],
            'loan_ledger_id' => ['nullable', 'integer', Rule::exists('acct_ledgers', 'id')->where('company_id', $company->id)],
            'interest_ledger_id' => ['nullable', 'integer', Rule::exists('acct_ledgers', 'id')->where('company_id', $company->id)],
            'bank_account_id' => ['nullable', 'integer', Rule::exists('acct_bank_accounts', 'id')->where('company_id', $company->id)],
            'overdraft_limit' => ['nullable', 'numeric', 'min:0'],
        ]);

        $loan = $service->create($company, $data + [
            'is_long_term' => $request->boolean('is_long_term', true),
        ]);

        return redirect()->to(tally_route('books.tally.loans.show', $loan))
            ->with('status', 'Loan created with EMI schedule.');
    }

    public function show(WorkspaceContext $context, Loan $loan): View
    {
        abort_unless($context->company() && $loan->company_id === $context->company()->id, 404);
        $loan->load(['schedules', 'bankAccount', 'loanLedger', 'interestLedger']);

        $company = $context->company();

        return view('tally::loans.show', [
            'company' => $company,
            'loan' => $loan,
            'bankLedgers' => Ledger::query()
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->with('accountGroup')
                ->orderBy('name')
                ->get()
                ->filter(fn (Ledger $l) => $l->isCashOrBank()),
        ]);
    }

    public function disburse(WorkspaceContext $context, Loan $loan, LoanService $service): RedirectResponse
    {
        abort_unless($context->company() && $loan->company_id === $context->company()->id, 404);

        $service->disburse($loan, request()->user());

        return back()->with('status', 'Loan disbursement posted.');
    }

    public function paySchedule(
        Request $request,
        WorkspaceContext $context,
        Loan $loan,
        LoanSchedule $schedule,
        LoanService $service,
    ): RedirectResponse {
        abort_unless($context->company() && $loan->company_id === $context->company()->id, 404);
        abort_unless($schedule->loan_id === $loan->id, 404);

        $data = $request->validate([
            'bank_ledger_id' => ['nullable', 'integer', Rule::exists('acct_ledgers', 'id')->where('company_id', $loan->company_id)],
        ]);

        $service->payInstallment($schedule, $request->user(), $data['bank_ledger_id'] ?? null);

        return back()->with('status', 'EMI installment #'.$schedule->installment_no.' paid.');
    }
}
