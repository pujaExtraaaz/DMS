<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Models\BusinessUnit;

class BusinessUnitController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Business units',
                'message' => 'Select a company before managing business units.',
            ]);
        }

        return view('tally::business-units.index', [
            'company' => $company,
            'units' => BusinessUnit::query()
                ->where('company_id', $company->id)
                ->orderBy('name')
                ->paginate(25),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('acct_business_units', 'name')->where('company_id', $company->id)],
            'code' => ['nullable', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        BusinessUnit::query()->create([
            'company_id' => $company->id,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('status', 'Business unit created.');
    }

    public function update(Request $request, WorkspaceContext $context, BusinessUnit $businessUnit): RedirectResponse
    {
        abort_unless($context->company() && $businessUnit->company_id === $context->company()->id, 404);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('acct_business_units', 'name')
                    ->where('company_id', $context->company()->id)
                    ->ignore($businessUnit->id),
            ],
            'code' => ['nullable', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $businessUnit->update([
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('status', 'Business unit updated.');
    }
}
