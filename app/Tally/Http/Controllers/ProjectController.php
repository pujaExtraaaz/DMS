<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Invoicing\InvoiceKind;
use Tally\Invoicing\PartyDirectory;
use Tally\Models\Project;
use Tally\Models\ProjectMilestone;

class ProjectController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', ['title' => 'Projects', 'message' => 'Select a company first.']);
        }

        return view('tally::projects.index', [
            'company' => $company,
            'projects' => Project::query()->with('customer')->where('company_id', $company->id)->orderBy('name')->paginate(25),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, PartyDirectory $parties): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160', Rule::unique('acct_projects', 'name')->where('company_id', $company->id)],
            'code' => ['nullable', 'string', 'max:40'],
            'customer_ledger_id' => ['nullable', 'integer', Rule::exists('acct_ledgers', 'id')->where('company_id', $company->id)],
            'contract_value' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        Project::query()->create([
            'company_id' => $company->id,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'customer_ledger_id' => $data['customer_ledger_id'] ?? null,
            'contract_value' => $data['contract_value'] ?? 0,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'status' => 'open',
            'is_active' => true,
        ]);

        return back()->with('status', 'Project created.');
    }

    public function show(WorkspaceContext $context, Project $project): View
    {
        abort_unless($context->company() && $project->company_id === $context->company()->id, 404);
        $project->load(['milestones.billedInvoice', 'customer']);

        return view('tally::projects.show', [
            'company' => $context->company(),
            'project' => $project,
        ]);
    }

    public function storeMilestone(Request $request, WorkspaceContext $context, Project $project): RedirectResponse
    {
        abort_unless($context->company() && $project->company_id === $context->company()->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
        ]);

        $amount = $data['amount'] ?? null;
        if ($amount === null && isset($data['percent'])) {
            $amount = round(((float) $project->contract_value) * ((float) $data['percent'] / 100), 2);
        }

        $project->milestones()->create([
            'name' => $data['name'],
            'percent' => $data['percent'] ?? 0,
            'amount' => $amount ?? 0,
            'due_date' => $data['due_date'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('status', 'Milestone added.');
    }

    public function billMilestone(WorkspaceContext $context, Project $project, ProjectMilestone $milestone): RedirectResponse
    {
        abort_unless(
            $context->company()
            && $project->company_id === $context->company()->id
            && $milestone->project_id === $project->id,
            404
        );

        return redirect()->to(tally_route('books.tally.invoices.sales.create', [
            'project_milestone' => $milestone->id,
            'party_ledger_id' => $project->customer_ledger_id,
            'billing_mode' => 'milestone',
        ]));
    }
}
