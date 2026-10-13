<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Models\StatutoryDueDate;
use Tally\Tax\StatutoryCalendarService;

class StatutoryCalendarController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Statutory calendar',
                'message' => 'Select a company before viewing statutory due dates.',
            ]);
        }

        return view('tally::tax.statutory-calendar', [
            'company' => $company,
            'dues' => StatutoryDueDate::query()
                ->where('company_id', $company->id)
                ->orderBy('due_date')
                ->paginate(50),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, StatutoryCalendarService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'obligation' => ['required', 'string', 'max:120'],
            'category' => ['required', Rule::in(['gst', 'tds', 'income_tax', 'other'])],
            'due_date' => ['required', 'date'],
            'period_label' => ['nullable', 'string', 'max:40'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        $service->create($company, $data);

        return back()->with('status', 'Due date added.');
    }

    public function seed(Request $request, WorkspaceContext $context, StatutoryCalendarService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'period' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $created = $service->seedMonth($company, $data['period']);

        return back()->with('status', count($created).' statutory due date(s) seeded.');
    }
}
