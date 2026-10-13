<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Tally\Analytics\KpiService;
use Tally\Context\WorkspaceContext;
use Tally\Reporting\FinancialReports;

class ManagementPackController extends Controller
{
    public function index(Request $request, WorkspaceContext $context, FinancialReports $reports, KpiService $kpis): View
    {
        $company = $context->company();
        $year = $context->financialYear();
        if (! $company || ! $year) {
            return view('tally::workspace.needs-company', [
                'title' => 'Management pack',
                'message' => 'Select a company and financial year first.',
            ]);
        }

        $from = $request->string('from')->toString() ?: $year->start_date->toDateString();
        $to = $request->string('to')->toString() ?: min(now()->toDateString(), $year->end_date->toDateString());
        if ($to < $year->start_date->toDateString()) {
            $to = $year->end_date->toDateString();
        }
        if ($to > $year->end_date->toDateString()) {
            $to = $year->end_date->toDateString();
        }
        $branchId = $context->branchId();

        return view('tally::management-pack.index', [
            'company' => $company,
            'year' => $year,
            'from' => $from,
            'to' => $to,
            'trialBalance' => $reports->trialBalance($company, $branchId, $year, $to),
            'profitAndLoss' => $reports->profitAndLoss($company, $branchId, $year, $to),
            'balanceSheet' => $reports->balanceSheet($company, $branchId, $year, $to),
            'kpi' => $kpis->snapshot($company, $to),
        ]);
    }
}
