<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Models\Ledger;
use Tally\Models\VendorRetention;
use Tally\Payables\VendorRetentionService;

class VendorRetentionController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Vendor retentions',
                'message' => 'Select a company first.',
            ]);
        }

        return view('tally::vendor-retentions.index', [
            'company' => $company,
            'retentions' => VendorRetention::query()
                ->with(['supplier', 'invoice', 'retentionLedger'])
                ->where('company_id', $company->id)
                ->latest('retention_date')
                ->paginate(25),
            'suppliers' => Ledger::query()
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->with('accountGroup')
                ->orderBy('name')
                ->get()
                ->filter(fn (Ledger $l) => $l->isSupplier()),
            'ledgers' => Ledger::query()
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'bankLedgers' => Ledger::query()
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->with('accountGroup')
                ->orderBy('name')
                ->get()
                ->filter(fn (Ledger $l) => $l->isCashOrBank()),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, VendorRetentionService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'invoice_id' => ['nullable', 'integer', Rule::exists('acct_invoices', 'id')->where('company_id', $company->id)],
            'supplier_ledger_id' => ['nullable', 'integer', Rule::exists('acct_ledgers', 'id')->where('company_id', $company->id)],
            'retention_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'retention_amount' => ['nullable', 'numeric', 'min:0'],
            'retention_date' => ['required', 'date'],
            'release_due_date' => ['nullable', 'date'],
            'kind' => ['nullable', Rule::in(['retention', 'security_deposit'])],
            'retention_ledger_id' => ['nullable', 'integer', Rule::exists('acct_ledgers', 'id')->where('company_id', $company->id)],
            'post_journal' => ['nullable', 'boolean'],
        ]);

        $service->hold($company, $request->user(), $data + [
            'post_journal' => $request->boolean('post_journal', true),
        ]);

        return back()->with('status', 'Vendor retention held.');
    }

    public function release(
        Request $request,
        WorkspaceContext $context,
        VendorRetention $vendorRetention,
        VendorRetentionService $service,
    ): RedirectResponse {
        $company = $context->company();
        abort_unless($company && $vendorRetention->company_id === $company->id, 404);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'bank_ledger_id' => ['nullable', 'integer', Rule::exists('acct_ledgers', 'id')->where('company_id', $company->id)],
            'date' => ['nullable', 'date'],
        ]);

        $service->release(
            $vendorRetention,
            $data['amount'],
            $request->user(),
            $data['bank_ledger_id'] ?? null,
            $data['date'] ?? null,
        );

        return back()->with('status', 'Retention release posted.');
    }
}
