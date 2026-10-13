<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Tally\Context\WorkspaceContext;
use Tally\Reporting\ComparativeReport;

class ComparativeReportController extends Controller
{
    public function index(Request $request, WorkspaceContext $context, ComparativeReport $report): View
    {
        $company = $context->company();
        $year = $context->financialYear();

        if (! $company || ! $year) {
            return view('tally::workspace.needs-company', [
                'title' => 'Comparative report',
                'message' => 'Select a company and financial year first.',
            ]);
        }

        $from = $request->string('from', $year->start_date->toDateString())->toString();
        $to = $request->string('to', $year->end_date->toDateString())->toString();
        $comparison = $report->yearOverYear($company, $from, $to, $context->branch()?->id);

        return view('tally::comparative-report.index', [
            'company' => $company,
            'year' => $year,
            'from' => $from,
            'to' => $to,
            'comparison' => $comparison,
        ]);
    }
}
