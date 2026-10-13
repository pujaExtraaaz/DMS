<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Tally\Context\WorkspaceContext;
use Tally\Tax\AdvanceTaxService;

class AdvanceTaxController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Advance tax',
                'message' => 'Select a company first.',
            ]);
        }

        return view('tally::tax.advance-tax', [
            'company' => $company,
            'ledgers' => $company->ledgers()->with('accountGroup')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, AdvanceTaxService $service): RedirectResponse
    {
        $company = $context->company();
        $branch = $context->branch();
        $year = $context->financialYear();
        abort_unless($company && $branch && $year, 404);

        $data = $request->validate([
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'advance_tax_ledger_id' => ['required', 'integer'],
            'bank_ledger_id' => ['required', 'integer'],
            'challan_number' => ['nullable', 'string', 'max:80'],
            'narration' => ['nullable', 'string', 'max:500'],
        ]);

        $voucher = $service->post($company, $branch, $year, $request->user(), $data);

        return redirect()->to(tally_route('books.tally.vouchers.show', $voucher))
            ->with('status', 'Advance tax payment posted.');
    }
}
