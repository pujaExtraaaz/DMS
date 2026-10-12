<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Models\Company;
use Tally\Models\Ledger;
use Tally\Models\LedgerMirror;

class LedgerMirrorController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Ledger mirrors',
                'message' => 'Select a company before mapping intercompany ledgers.',
            ]);
        }

        return view('tally::ledger-mirrors.index', [
            'company' => $company,
            'mirrors' => LedgerMirror::query()
                ->with(['sourceLedger', 'counterpartyCompany', 'counterpartyLedger'])
                ->where('source_company_id', $company->id)
                ->orderBy('id')
                ->paginate(30),
            'ledgers' => Ledger::query()->where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get(),
            'companies' => Company::query()->where('is_active', true)->whereKeyNot($company->id)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'source_ledger_id' => [
                'required',
                'integer',
                Rule::exists('acct_ledgers', 'id')->where('company_id', $company->id),
            ],
            'counterparty_company_id' => [
                'required',
                'integer',
                Rule::exists('acct_companies', 'id')->where(fn ($q) => $q->whereKeyNot($company->id)),
            ],
            'counterparty_ledger_id' => [
                'required',
                'integer',
                Rule::exists('acct_ledgers', 'id')->where(fn ($q) => $q->where('company_id', (int) $request->input('counterparty_company_id'))),
            ],
        ]);

        LedgerMirror::query()->updateOrCreate(
            [
                'source_ledger_id' => $data['source_ledger_id'],
                'counterparty_company_id' => $data['counterparty_company_id'],
            ],
            [
                'source_company_id' => $company->id,
                'counterparty_ledger_id' => $data['counterparty_ledger_id'],
            ],
        );

        return back()->with('status', 'Ledger mirror saved.');
    }

    public function destroy(WorkspaceContext $context, LedgerMirror $ledgerMirror): RedirectResponse
    {
        abort_unless($context->company() && $ledgerMirror->source_company_id === $context->company()->id, 404);
        $ledgerMirror->delete();

        return back()->with('status', 'Ledger mirror removed.');
    }
}
