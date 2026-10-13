<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Tally\Context\WorkspaceContext;
use Tally\Models\GstChallan;
use Tally\Tax\GstChallanService;

class GstChallanController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'GST challans',
                'message' => 'Select a company before managing GST challans.',
            ]);
        }

        return view('tally::tax.gst-challans', [
            'company' => $company,
            'challans' => GstChallan::query()
                ->where('company_id', $company->id)
                ->orderByDesc('payment_date')
                ->paginate(40),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, GstChallanService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'return_period' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'challan_number' => ['nullable', 'string', 'max:40'],
            'payment_date' => ['required', 'date'],
            'cgst' => ['nullable', 'numeric', 'min:0'],
            'sgst' => ['nullable', 'numeric', 'min:0'],
            'igst' => ['nullable', 'numeric', 'min:0'],
            'cess' => ['nullable', 'numeric', 'min:0'],
            'interest' => ['nullable', 'numeric', 'min:0'],
            'penalty' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        $service->save($company, $data);

        return back()->with('status', 'GST challan saved.');
    }
}
