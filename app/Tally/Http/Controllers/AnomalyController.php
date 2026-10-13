<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Tally\Context\WorkspaceContext;
use Tally\Controls\AnomalyDetectionService;
use Tally\Models\AnomalyFlag;

class AnomalyController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Anomalies',
                'message' => 'Select a company first.',
            ]);
        }

        return view('tally::anomalies.index', [
            'company' => $company,
            'flags' => AnomalyFlag::query()
                ->where('company_id', $company->id)
                ->latest('id')
                ->paginate(50),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, AnomalyDetectionService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $flags = $service->scan($company);

        return back()->with('status', count($flags).' anomaly flag(s) processed.');
    }
}
