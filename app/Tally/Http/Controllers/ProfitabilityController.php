<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Analytics\ProfitabilityService;
use Tally\Context\WorkspaceContext;
use Tally\Models\ProfitabilitySnapshot;

class ProfitabilityController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Profitability',
                'message' => 'Select a company first.',
            ]);
        }

        return view('tally::profitability.index', [
            'company' => $company,
            'snapshots' => ProfitabilitySnapshot::query()
                ->where('company_id', $company->id)
                ->latest('to_date')
                ->paginate(25),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, ProfitabilityService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'dimension' => ['required', Rule::in(['product', 'customer', 'branch', 'project', 'segment'])],
            'dimension_key' => ['required', 'string', 'max:80'],
            'dimension_label' => ['required', 'string', 'max:160'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'revenue' => ['required', 'numeric', 'min:0'],
            'cost' => ['required', 'numeric', 'min:0'],
        ]);

        $service->snapshot($company, $data);

        return back()->with('status', 'Profitability snapshot saved.');
    }
}
