<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Tally\Context\WorkspaceContext;
use Tally\Models\Bill;
use Tally\Models\Ledger;
use Tally\Models\Party;
use Tally\Receivables\AgeingReport;
use Tally\Receivables\PartyStatementService;
use Tally\Accounting\OpeningBalanceType;

class PayablesReportController extends Controller
{
    public function ageing(Request $request, WorkspaceContext $context, AgeingReport $report): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', ['title' => 'Payables ageing', 'message' => 'Select a company first.']);
        }

        $asOf = $request->input('as_on', now()->toDateString());
        $data = $report->payables($company, $asOf, $context->branchId());

        return view('tally::reports.supplier-ageing', [
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
            return view('tally::workspace.needs-company', ['title' => 'Supplier statement', 'message' => 'Select a company first.']);
        }

        $ledgerId = (int) $request->input('ledger_id');
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());
        $party = $ledgerId
            ? Ledger::query()->where('company_id', $company->id)->whereKey($ledgerId)->first()
            : null;

        $statement = $party
            ? $statements->supplierBillWise($company, $party, $from, $to)
            : null;

        return view('tally::reports.supplier-statement', [
            'company' => $company,
            'suppliers' => $company->ledgers()->with('accountGroup')->orderBy('name')->get()->filter->isSupplier(),
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
        $statement = $statements->supplierBillWise($company, $ledger, $company->financial_year_start->toDateString(), $asOn);

        return view('tally::reports.account-confirmation', [
            'company' => $company,
            'statement' => $statement,
            'asOn' => $asOn,
        ]);
    }

    public function dueCalendar(Request $request, WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', ['title' => 'Payment due calendar', 'message' => 'Select a company first.']);
        }

        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->endOfMonth()->toDateString());

        $bills = Bill::query()
            ->with('ledger')
            ->where('company_id', $company->id)
            ->where('side', OpeningBalanceType::Credit)
            ->whereBetween('due_date', [$from, $to])
            ->orderBy('due_date')
            ->get()
            ->filter(fn (Bill $bill) => $bill->isActive() && $bill->ledger?->isSupplier());

        return view('tally::reports.payment-due-calendar', [
            'company' => $company,
            'bills' => $bills,
            'filters' => compact('from', 'to'),
        ]);
    }

    public function msmePayables(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', ['title' => 'MSME payables', 'message' => 'Select a company first.']);
        }

        $msmeParties = Party::query()
            ->with('ledger')
            ->where('company_id', $company->id)
            ->where('is_msme', true)
            ->where('is_active', true)
            ->orderBy('legal_name')
            ->get();

        $ledgerIds = $msmeParties->pluck('ledger_id')->filter()->all();

        $bills = Bill::query()
            ->with('ledger')
            ->where('company_id', $company->id)
            ->where('side', OpeningBalanceType::Credit)
            ->whereIn('ledger_id', $ledgerIds ?: [0])
            ->orderBy('due_date')
            ->get()
            ->filter(fn (Bill $bill) => $bill->isActive());

        return view('tally::reports.msme-payables', [
            'company' => $company,
            'parties' => $msmeParties,
            'bills' => $bills,
        ]);
    }
}
