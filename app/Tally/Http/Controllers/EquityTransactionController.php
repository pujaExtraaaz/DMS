<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Accounting\EquityService;
use Tally\Context\WorkspaceContext;
use Tally\Models\EquityTransaction;
use Tally\Models\Ledger;

class EquityTransactionController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Equity transactions',
                'message' => 'Select a company first.',
            ]);
        }

        return view('tally::equity-transactions.index', [
            'company' => $company,
            'transactions' => EquityTransaction::query()
                ->with('ledger')
                ->where('company_id', $company->id)
                ->latest('txn_date')
                ->paginate(25),
            'ledgers' => Ledger::query()->where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, EquityService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'kind' => ['required', Rule::in(['capital', 'drawing', 'dividend', 'reserve'])],
            'txn_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'ledger_id' => ['nullable', 'integer', Rule::exists('acct_ledgers', 'id')->where('company_id', $company->id)],
            'narration' => ['nullable', 'string', 'max:500'],
        ]);

        $service->record($company, $data);

        return back()->with('status', 'Equity transaction recorded.');
    }
}
