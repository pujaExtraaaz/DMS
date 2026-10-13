<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Models\TdsCertificate;
use Tally\Models\TdsChallan;
use Tally\Tax\TdsComplianceService;

class TdsComplianceController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'TDS compliance',
                'message' => 'Select a company before managing TDS challans and certificates.',
            ]);
        }

        return view('tally::tax.tds-compliance', [
            'company' => $company,
            'challans' => TdsChallan::query()
                ->where('company_id', $company->id)
                ->orderByDesc('deposit_date')
                ->paginate(20, ['*'], 'challans_page'),
            'certificates' => TdsCertificate::query()
                ->with('partyLedger')
                ->where('company_id', $company->id)
                ->orderByDesc('id')
                ->paginate(20, ['*'], 'certs_page'),
            'ledgers' => $company->ledgers()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeChallan(Request $request, WorkspaceContext $context, TdsComplianceService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'return_period' => ['required', 'string', 'max:7'],
            'section' => ['nullable', 'string', 'max:20'],
            'challan_number' => ['nullable', 'string', 'max:40'],
            'bsr_code' => ['nullable', 'string', 'max:20'],
            'deposit_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'interest' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        $service->saveChallan($company, $data);

        return back()->with('status', 'TDS challan saved.');
    }

    public function storeCertificate(Request $request, WorkspaceContext $context, TdsComplianceService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'party_ledger_id' => ['required', 'integer', Rule::exists('acct_ledgers', 'id')->where('company_id', $company->id)],
            'certificate_number' => [
                'required', 'string', 'max:40',
                Rule::unique('acct_tds_certificates', 'certificate_number')->where('company_id', $company->id),
            ],
            'form_type' => ['nullable', 'string', 'max:20'],
            'financial_year' => ['required', 'string', 'max:9'],
            'quarter' => ['nullable', 'string', 'max:2'],
            'tds_amount' => ['required', 'numeric', 'min:0'],
            'issue_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        $service->saveCertificate($company, $data);

        return back()->with('status', 'TDS certificate saved.');
    }
}
