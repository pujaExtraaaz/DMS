<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Models\Ledger;
use Tally\Models\RelatedParty;

class RelatedPartyController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Related parties',
                'message' => 'Select a company first.',
            ]);
        }

        return view('tally::related-parties.index', [
            'company' => $company,
            'parties' => RelatedParty::query()
                ->with('partyLedger')
                ->where('company_id', $company->id)
                ->orderBy('id')
                ->paginate(25),
            'ledgers' => Ledger::query()->where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'party_ledger_id' => [
                'required', 'integer',
                Rule::exists('acct_ledgers', 'id')->where('company_id', $company->id),
                Rule::unique('acct_related_parties', 'party_ledger_id')->where('company_id', $company->id),
            ],
            'relationship' => ['required', 'string', 'max:80'],
        ]);

        RelatedParty::query()->create([
            'company_id' => $company->id,
            'party_ledger_id' => $data['party_ledger_id'],
            'relationship' => $data['relationship'],
            'is_active' => true,
        ]);

        return back()->with('status', 'Related party added.');
    }
}
