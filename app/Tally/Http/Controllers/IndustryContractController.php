<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Industry\IndustryContractService;
use Tally\Models\IndustryContract;
use Tally\Models\Ledger;

class IndustryContractController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Industry contracts',
                'message' => 'Select a company first.',
            ]);
        }

        return view('tally::industry-contracts.index', [
            'company' => $company,
            'contracts' => IndustryContract::query()
                ->with('partyLedger')
                ->where('company_id', $company->id)
                ->latest('id')
                ->paginate(25),
            'ledgers' => Ledger::query()->where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, IndustryContractService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'industry' => ['required', Rule::in(['construction', 'subscription', 'retail', 'education', 'nonprofit', 'franchise'])],
            'contract_number' => ['required', 'string', 'max:40', Rule::unique('acct_industry_contracts', 'contract_number')->where('company_id', $company->id)],
            'name' => ['required', 'string', 'max:160'],
            'party_ledger_id' => ['nullable', 'integer', Rule::exists('acct_ledgers', 'id')->where('company_id', $company->id)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'contract_value' => ['nullable', 'numeric', 'min:0'],
            'retention_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'billing_frequency' => ['nullable', 'string', 'max:20'],
        ]);

        $contract = $service->save($company, $data);

        return redirect()->to(tally_route('books.tally.industry-contracts.show', $contract))
            ->with('status', 'Contract saved.');
    }

    public function show(WorkspaceContext $context, IndustryContract $industryContract): View
    {
        abort_unless($context->company() && $industryContract->company_id === $context->company()->id, 404);
        $industryContract->load('partyLedger');

        return view('tally::industry-contracts.show', [
            'company' => $context->company(),
            'contract' => $industryContract,
        ]);
    }
}
