<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Banking\BankCategorizationService;
use Tally\Context\WorkspaceContext;
use Tally\Models\BankCategorizationRule;

class BankCategorizationController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Bank categorization',
                'message' => 'Select a company before managing bank categorization rules.',
            ]);
        }

        return view('tally::banking.rules', [
            'company' => $company,
            'rules' => BankCategorizationRule::query()
                ->with('ledger')
                ->where('company_id', $company->id)
                ->orderBy('priority')
                ->orderBy('name')
                ->paginate(40),
            'ledgers' => $company->ledgers()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, BankCategorizationService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'match_field' => ['required', Rule::in(['narration', 'reference', 'amount'])],
            'match_type' => ['required', Rule::in(['contains', 'equals', 'starts_with', 'regex'])],
            'match_value' => ['required', 'string', 'max:255'],
            'ledger_id' => ['required', 'integer', Rule::exists('acct_ledgers', 'id')->where('company_id', $company->id)],
            'priority' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        $service->save($company, $data);

        return back()->with('status', 'Categorization rule saved.');
    }

    public function destroy(WorkspaceContext $context, BankCategorizationRule $rule, BankCategorizationService $service): RedirectResponse
    {
        abort_unless($context->company() && $rule->company_id === $context->company()->id, 404);
        $service->delete($rule);

        return back()->with('status', 'Rule deleted.');
    }

    public function apply(WorkspaceContext $context, BankCategorizationService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);
        $count = $service->apply($company);

        return back()->with('status', $count.' statement line(s) categorized.');
    }
}
