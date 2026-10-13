<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Models\ContingentLiability;

class ContingentLiabilityController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Contingent liabilities',
                'message' => 'Select a company first.',
            ]);
        }

        return view('tally::contingent-liabilities.index', [
            'company' => $company,
            'items' => ContingentLiability::query()
                ->where('company_id', $company->id)
                ->latest('as_of_date')
                ->paginate(25),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'kind' => ['nullable', Rule::in(['guarantee', 'contingent', 'provision'])],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'as_of_date' => ['required', 'date'],
            'counterparty' => ['nullable', 'string', 'max:160'],
        ]);

        ContingentLiability::query()->create([
            'company_id' => $company->id,
            'kind' => $data['kind'] ?? 'guarantee',
            'description' => $data['description'],
            'amount' => $data['amount'],
            'as_of_date' => $data['as_of_date'],
            'counterparty' => $data['counterparty'] ?? null,
            'status' => 'open',
        ]);

        return back()->with('status', 'Contingent liability recorded.');
    }
}
