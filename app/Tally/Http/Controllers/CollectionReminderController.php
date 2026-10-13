<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Tally\Context\WorkspaceContext;
use Tally\Models\CollectionReminder;
use Tally\Receivables\CollectionReminderService;

class CollectionReminderController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Collection reminders',
                'message' => 'Select a company first.',
            ]);
        }

        return view('tally::collection-reminders.index', [
            'company' => $company,
            'reminders' => CollectionReminder::query()
                ->with(['bill', 'ledger'])
                ->where('company_id', $company->id)
                ->latest('id')
                ->paginate(40),
        ]);
    }

    public function run(WorkspaceContext $context, CollectionReminderService $reminders): RedirectResponse
    {
        abort_unless($context->company(), 404);
        $queued = $reminders->queueDue();
        $forCompany = collect($queued)->where('company_id', $context->company()->id)->count();

        return back()->with('status', $forCompany.' reminder(s) queued for '.$context->company()->name.'.');
    }
}
