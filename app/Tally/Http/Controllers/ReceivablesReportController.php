<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Models\Bill;
use Tally\Models\DeferredRevenueSchedule;
use Tally\Models\Ledger;
use Tally\Receivables\AgeingReport;
use Tally\Receivables\BadDebtService;
use Tally\Receivables\CollectionReminderService;
use Tally\Receivables\DeferredRevenueService;
use Tally\Receivables\PartyStatementService;
use Tally\Accounting\Money;

class ReceivablesReportController extends Controller
{
    public function ageing(Request $request, WorkspaceContext $context, AgeingReport $report): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', ['title' => 'Ageing', 'message' => 'Select a company first.']);
        }

        $asOf = $request->input('as_on', now()->toDateString());
        $data = $report->receivables($company, $asOf, $context->branchId());

        return view('tally::reports.ageing', [
            'company' => $company,
            'asOn' => $asOf,
            'rows' => $data['rows'],
            'totals' => $data['totals'],
        ]);
    }

    public function statement(Request $request, WorkspaceContext $context, PartyStatementService $statements): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', ['title' => 'Customer statement', 'message' => 'Select a company first.']);
        }

        $ledgerId = (int) $request->input('ledger_id');
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());
        $party = $ledgerId
            ? Ledger::query()->where('company_id', $company->id)->whereKey($ledgerId)->first()
            : null;

        $statement = $party
            ? $statements->billWise($company, $party, $from, $to)
            : null;

        return view('tally::reports.customer-statement', [
            'company' => $company,
            'customers' => $company->ledgers()->with('accountGroup')->orderBy('name')->get()->filter->isCustomer(),
            'statement' => $statement,
            'filters' => compact('ledgerId', 'from', 'to'),
        ]);
    }

    public function confirmation(Request $request, WorkspaceContext $context, PartyStatementService $statements): View
    {
        $company = $context->company();
        abort_unless($company, 404);
        $ledger = Ledger::query()->where('company_id', $company->id)->whereKey((int) $request->input('ledger_id'))->firstOrFail();
        $asOn = $request->input('as_on', now()->toDateString());
        $statement = $statements->billWise($company, $ledger, $company->financial_year_start->toDateString(), $asOn);

        return view('tally::reports.account-confirmation', [
            'company' => $company,
            'statement' => $statement,
            'asOn' => $asOn,
        ]);
    }

    public function provision(Request $request, WorkspaceContext $context, AgeingReport $ageing, BadDebtService $badDebts): RedirectResponse
    {
        $company = $context->company();
        $year = $context->financialYear();
        abort_unless($company && $year, 404);

        $asOn = $request->input('as_on', now()->toDateString());
        $data = $ageing->receivables($company, $asOn, $context->branchId());
        $cents = [];
        foreach (['current', 'b1_30', 'b31_60', 'b61_90', 'b90_plus'] as $key) {
            $cents[$key] = Money::cents($data['totals'][$key]);
        }

        $rates = $request->validate([
            'expense_percent_current' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'b1_30' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'b31_60' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'b61_90' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'b90_plus' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $badDebts->provisionJournal($company, $year, $request->user(), $cents, $rates, $asOn);

        return back()->with('status', 'Doubtful debt provision posted.');
    }

    public function writeOff(Request $request, WorkspaceContext $context, BadDebtService $badDebts): RedirectResponse
    {
        $company = $context->company();
        $year = $context->financialYear();
        abort_unless($company && $year, 404);

        $data = $request->validate([
            'bill_id' => ['required', 'integer', Rule::exists('acct_bills', 'id')->where('company_id', $company->id)],
            'as_on' => ['nullable', 'date'],
        ]);

        $bill = Bill::query()->findOrFail($data['bill_id']);
        $badDebts->writeOff($bill, $company, $year, $request->user(), $data['as_on'] ?? now()->toDateString());

        return back()->with('status', 'Bill written off.');
    }

    public function runReminders(CollectionReminderService $reminders): RedirectResponse
    {
        $queued = $reminders->queueDue();

        return back()->with('status', count($queued).' collection reminder(s) queued.');
    }

    public function deferred(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', ['title' => 'Deferred revenue', 'message' => 'Select a company first.']);
        }

        return view('tally::deferred-revenue.index', [
            'company' => $company,
            'schedules' => DeferredRevenueSchedule::query()->where('company_id', $company->id)->latest('id')->paginate(25),
            'ledgers' => $company->ledgers()->orderBy('name')->get(),
        ]);
    }

    public function storeDeferred(Request $request, WorkspaceContext $context, DeferredRevenueService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'customer_ledger_id' => ['required', 'integer'],
            'revenue_ledger_id' => ['required', 'integer'],
            'deferred_ledger_id' => ['required', 'integer'],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'invoice_id' => ['nullable', 'integer'],
        ]);

        $service->create($data + [
            'company_id' => $company->id,
            'frequency' => 'monthly',
            'next_recognize_on' => $data['start_date'],
        ]);

        return back()->with('status', 'Deferred revenue schedule created.');
    }

    public function recognizeDeferred(DeferredRevenueService $service): RedirectResponse
    {
        $done = $service->recognizeDue();

        return back()->with('status', count($done).' schedule(s) recognized.');
    }

    public function unbilled(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', ['title' => 'Unbilled revenue', 'message' => 'Select a company first.']);
        }

        return view('tally::unbilled-revenue.index', [
            'company' => $company,
            'ledgers' => $company->ledgers()->orderBy('name')->get(),
        ]);
    }

    public function storeUnbilled(Request $request, WorkspaceContext $context, \Tally\Receivables\UnbilledRevenueService $service): RedirectResponse
    {
        $company = $context->company();
        $year = $context->financialYear();
        abort_unless($company && $year, 404);

        $data = $request->validate([
            'accrued_income_ledger_id' => ['required', 'integer'],
            'revenue_ledger_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'auto_reverse' => ['nullable', 'boolean'],
        ]);

        $service->accrue(
            $company,
            $year,
            $request->user(),
            (int) $data['accrued_income_ledger_id'],
            (int) $data['revenue_ledger_id'],
            (string) $data['amount'],
            $data['date'],
            $request->boolean('auto_reverse', true),
        );

        return back()->with('status', 'Unbilled revenue accrued.');
    }
}
