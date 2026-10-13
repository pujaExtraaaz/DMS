<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Tally\Context\WorkspaceContext;
use Tally\Reporting\BreakEvenReport;

class BreakEvenController extends Controller
{
    public function __invoke(Request $request, WorkspaceContext $context, BreakEvenReport $report): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Break-even / CVP',
                'message' => 'Select a company first.',
            ]);
        }

        $input = $request->validate([
            'fixed_costs' => ['nullable', 'numeric', 'min:0'],
            'price_per_unit' => ['nullable', 'numeric', 'min:0'],
            'variable_cost_per_unit' => ['nullable', 'numeric', 'min:0'],
            'contribution_margin_percent' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
        ]);

        $result = ($input['fixed_costs'] ?? null) !== null
            ? $report->calculate($input)
            : null;

        return view('tally::reports.break-even', [
            'company' => $company,
            'input' => $input,
            'result' => $result,
        ]);
    }
}
