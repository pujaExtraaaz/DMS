<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Tally\Banking\CashForecastService;
use Tally\Context\WorkspaceContext;
use Tally\Models\CashForecast;

class CashForecastController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Cash forecast',
                'message' => 'Select a company before managing cash forecasts.',
            ]);
        }

        return view('tally::banking.forecasts', [
            'company' => $company,
            'forecasts' => CashForecast::query()
                ->where('company_id', $company->id)
                ->orderByDesc('as_of_date')
                ->paginate(25),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, CashForecastService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'as_of_date' => ['required', 'date'],
            'horizon_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'opening_cash' => ['required', 'numeric'],
            'projected_inflows' => ['nullable', 'numeric'],
            'projected_outflows' => ['nullable', 'numeric'],
            'scenario' => ['nullable', 'string', 'max:40'],
            'lines_json' => ['nullable', 'array'],
        ]);

        $service->save($company, $data);

        return back()->with('status', 'Cash forecast saved.');
    }
}
