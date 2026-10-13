<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Tally\Analytics\KpiService;
use Tally\Context\WorkspaceContext;
use Tally\Models\KpiSnapshot;

class KpiDashboardController extends Controller
{
    public function index(Request $request, WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'KPI dashboard',
                'message' => 'Select a company first.',
            ]);
        }

        $asOf = $request->string('as_of', now()->toDateString())->toString();
        $latest = KpiSnapshot::query()
            ->where('company_id', $company->id)
            ->whereDate('as_of_date', '<=', $asOf)
            ->latest('as_of_date')
            ->first();

        return view('tally::kpi.index', [
            'company' => $company,
            'asOf' => $asOf,
            'snapshot' => $latest,
            'history' => KpiSnapshot::query()
                ->where('company_id', $company->id)
                ->latest('as_of_date')
                ->limit(12)
                ->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, KpiService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'as_of' => ['required', 'date'],
            'days' => ['nullable', 'integer', 'min:1', 'max:3660'],
        ]);

        $service->snapshot($company, $data['as_of'], (int) ($data['days'] ?? 365));

        return back()->with('status', 'KPI snapshot stored.');
    }
}
