<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Controls\BalanceSheetReconService;
use Tally\Models\BalanceSheetReconciliation;
use Tally\Models\Ledger;

class BalanceSheetReconController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Balance sheet reconciliations',
                'message' => 'Select a company first.',
            ]);
        }

        return view('tally::balance-sheet-recons.index', [
            'company' => $company,
            'rows' => BalanceSheetReconciliation::query()
                ->with('ledger')
                ->where('company_id', $company->id)
                ->latest('as_of_date')
                ->paginate(25),
            'ledgers' => Ledger::query()->where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, BalanceSheetReconService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'ledger_id' => ['required', 'integer', Rule::exists('acct_ledgers', 'id')->where('company_id', $company->id)],
            'as_of_date' => ['required', 'date'],
            'gl_balance' => ['required', 'numeric'],
            'substantiated_balance' => ['required', 'numeric'],
            'evidence_note' => ['nullable', 'string', 'max:500'],
        ]);

        $service->upsert($company, $data + ['reviewed_by' => $request->user()?->id]);

        return back()->with('status', 'Reconciliation saved.');
    }
}
