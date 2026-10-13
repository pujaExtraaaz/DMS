<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Accounting\ConsolidationService;
use Tally\Context\WorkspaceContext;
use Tally\Models\Company;
use Tally\Models\Ledger;

class ConsolidationController extends Controller
{
    public function index(Request $request, WorkspaceContext $context, ConsolidationService $service): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Consolidation',
                'message' => 'Select a company first.',
            ]);
        }

        $asOf = $request->string('as_of', now()->toDateString())->toString();
        $result = $service->consolidatedTotals($company, $asOf);

        return view('tally::consolidation.index', [
            'company' => $company,
            'asOf' => $asOf,
            'maps' => $result['maps'],
            'totals' => $result['totals'],
            'companies' => Company::query()->where('is_active', true)->whereKeyNot($company->id)->orderBy('name')->get(),
            'elimLedgers' => Ledger::query()
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, ConsolidationService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'subsidiary_company_id' => ['required', 'integer', 'exists:acct_companies,id', Rule::notIn([$company->id])],
            'ownership_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'reporting_currency' => ['nullable', 'string', 'size:3'],
        ]);

        $service->upsertMap($company, $data);

        return back()->with('status', 'Consolidation map saved.');
    }

    public function worksheet(Request $request, WorkspaceContext $context, ConsolidationService $service): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Consolidation worksheet',
                'message' => 'Select a company first.',
            ]);
        }

        $from = $request->string('from')->toString() ?: null;
        $to = $request->string('to')->toString() ?: null;
        $sheet = $service->worksheet($company, $from, $to);

        return view('tally::consolidation.worksheet', [
            'company' => $company,
            'sheet' => $sheet,
        ]);
    }

    public function postEliminations(Request $request, WorkspaceContext $context, ConsolidationService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'elimination_ledger_id' => ['nullable', 'integer', Rule::exists('acct_ledgers', 'id')->where('company_id', $company->id)],
        ]);

        $posted = $service->postEliminationsForGroup(
            $company,
            $request->user(),
            $data['from'] ?? null,
            $data['to'] ?? null,
            $data['elimination_ledger_id'] ?? null,
        );

        return back()->with('status', count($posted).' elimination voucher(s) posted.');
    }
}
