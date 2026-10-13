<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Tally\Context\WorkspaceContext;
use Tally\Controls\CloseChecklistService;
use Tally\Models\CloseChecklist;

class CloseChecklistController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Close checklists',
                'message' => 'Select a company first.',
            ]);
        }

        return view('tally::close-checklists.index', [
            'company' => $company,
            'checklists' => CloseChecklist::query()
                ->withCount('tasks')
                ->where('company_id', $company->id)
                ->latest('id')
                ->paginate(25),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, CloseChecklistService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'period_label' => ['required', 'string', 'max:40'],
            'kind' => ['nullable', 'in:month,year'],
            'accounting_period_id' => ['nullable', 'integer'],
        ]);

        $checklist = $service->createMonthly($company, $data + [
            'owner_id' => $request->user()?->id,
        ]);

        return redirect()->to(tally_route('books.tally.close-checklists.show', $checklist))
            ->with('status', 'Close checklist created.');
    }

    public function show(WorkspaceContext $context, CloseChecklist $closeChecklist): View
    {
        abort_unless($context->company() && $closeChecklist->company_id === $context->company()->id, 404);
        $closeChecklist->load('tasks');

        return view('tally::close-checklists.show', [
            'company' => $context->company(),
            'checklist' => $closeChecklist,
        ]);
    }
}
