<?php

namespace Tally\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Tally\Accounting\IntercompanyService;
use Tally\Context\WorkspaceContext;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\Ledger;

class IntercompanyController extends Controller
{
    public function index(Request $request, WorkspaceContext $context, IntercompanyService $service): View
    {
        $company = $context->company();
        $year = $context->financialYear();

        if (! $company || ! $year) {
            return view('tally::workspace.needs-company', [
                'title' => 'Intercompany',
                'message' => 'Select a company and financial year first.',
            ]);
        }

        $from = $request->string('from', $year->start_date->toDateString())->toString();
        $to = $request->string('to', $year->end_date->toDateString())->toString();

        $worksheet = $service->consolidationWorksheet($company, $from, $to);

        return view('tally::intercompany.index', [
            'company' => $company,
            'year' => $year,
            'from' => $from,
            'to' => $to,
            'rows' => $worksheet['rows'],
            'worksheet' => $worksheet,
            'companies' => Company::query()->where('is_active', true)->whereKeyNot($company->id)->orderBy('name')->get(),
            'ledgers' => Ledger::query()->where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get(),
            'icLedgers' => Ledger::query()->where('company_id', $company->id)->where('is_intercompany', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, IntercompanyService $service): RedirectResponse
    {
        $company = $context->company();
        $branch = $context->branch();
        $year = $context->financialYear();
        abort_unless($company && $branch && $year, 404);

        $data = $request->validate([
            'counterparty_company_id' => ['required', 'integer', 'exists:acct_companies,id'],
            'voucher_date' => ['required', 'date'],
            'narration' => ['nullable', 'string', 'max:1000'],
            'ledger_id' => ['required', 'integer', 'exists:acct_ledgers,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'side' => ['required', 'in:debit,credit'],
            'source_ic_ledger_id' => ['nullable', 'integer', 'exists:acct_ledgers,id'],
        ]);

        $counterparty = Company::query()->findOrFail($data['counterparty_company_id']);
        $counterBranch = $counterparty->branches()->where('is_active', true)->firstOrFail();
        $counterYear = FinancialYear::query()
            ->where('company_id', $counterparty->id)
            ->where('is_active', true)
            ->where('is_closed', false)
            ->whereDate('start_date', '<=', $data['voucher_date'])
            ->whereDate('end_date', '>=', $data['voucher_date'])
            ->firstOrFail();

        $amount = $data['amount'];
        $entries = [[
            'ledger_id' => (int) $data['ledger_id'],
            'debit' => $data['side'] === 'debit' ? $amount : 0,
            'credit' => $data['side'] === 'credit' ? $amount : 0,
        ]];

        /** @var User $user */
        $user = $request->user();

        $service->postPairedJournal(
            $company,
            $branch,
            $year,
            $counterparty,
            $counterBranch,
            $counterYear,
            $user,
            $data['voucher_date'],
            $entries,
            $data['narration'] ?? null,
            $data['source_ic_ledger_id'] ?? null,
        );

        return back()->with('status', 'Intercompany pair posted.');
    }

    public function eliminate(Request $request, WorkspaceContext $context, IntercompanyService $service): RedirectResponse
    {
        $company = $context->company();
        $branch = $context->branch();
        $year = $context->financialYear();
        abort_unless($company && $branch && $year, 404);

        $data = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'elimination_ledger_id' => ['required', 'integer', 'exists:acct_ledgers,id'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $posted = $service->postEliminations(
            $company,
            $branch,
            $year,
            $user,
            $data['from'],
            $data['to'],
            (int) $data['elimination_ledger_id'],
        );

        return back()->with('status', count($posted).' elimination journal(s) posted.');
    }
}
