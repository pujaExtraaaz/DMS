<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Models\ItcEntry;
use Tally\Tax\ItcService;

class ItcController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'ITC register',
                'message' => 'Select a company before managing ITC entries.',
            ]);
        }

        return view('tally::tax.itc', [
            'company' => $company,
            'entries' => ItcEntry::query()
                ->with('invoice')
                ->where('company_id', $company->id)
                ->latest('id')
                ->paginate(40),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, ItcService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'invoice_id' => ['nullable', 'integer', Rule::exists('acct_invoices', 'id')->where('company_id', $company->id)],
            'return_period' => ['nullable', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'eligibility' => ['required', Rule::in(['eligible', 'ineligible', 'blocked', 'reversed'])],
            'igst' => ['nullable', 'numeric'],
            'cgst' => ['nullable', 'numeric'],
            'sgst' => ['nullable', 'numeric'],
            'cess' => ['nullable', 'numeric'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $service->record($company, $data);

        return back()->with('status', 'ITC entry recorded.');
    }
}
