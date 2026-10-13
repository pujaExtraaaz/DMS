<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Tally\Context\WorkspaceContext;
use Tally\Inventory\InventoryAgeingReport;
use Tally\Inventory\InventoryValuation;

class InventoryAgeingController extends Controller
{
    public function __invoke(Request $request, WorkspaceContext $context, InventoryAgeingReport $report, InventoryValuation $valuation): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Inventory ageing',
                'message' => 'Select a company first.',
            ]);
        }

        $asOn = $request->input('as_on') ?: now()->toDateString();
        $result = $report->run($company, $asOn);

        return view('tally::reports.inventory-ageing', [
            'company' => $company,
            'asOn' => $result['as_on'],
            'rows' => $result['rows'],
            'totals' => $result['totals'],
            'valuationNote' => $valuation->notesFor($company),
        ]);
    }
}
