<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Invoicing\InvoiceKind;
use Tally\Invoicing\PartyDirectory;
use Tally\Models\RequestForQuotation;
use Tally\Models\VendorQuotation;
use Tally\Purchasing\RfqService;

class RfqController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', ['title' => 'RFQs', 'message' => 'Select a company first.']);
        }

        return view('tally::rfqs.index', [
            'company' => $company,
            'rfqs' => RequestForQuotation::query()
                ->where('company_id', $company->id)
                ->when($context->financialYear(), fn ($q) => $q->where('financial_year_id', $context->financialYear()->id))
                ->latest('rfq_date')
                ->paginate(25),
        ]);
    }

    public function create(WorkspaceContext $context): View
    {
        $company = $context->company();
        $branch = $context->branch();
        $year = $context->financialYear();
        abort_unless($company && $branch && $year, 404);

        return view('tally::rfqs.form', [
            'company' => $company,
            'rfq' => new RequestForQuotation(['rfq_date' => now()->toDateString(), 'status' => 'draft']),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, RfqService $service): RedirectResponse
    {
        $company = $context->company();
        $branch = $context->branch();
        $year = $context->financialYear();
        abort_unless($company && $branch && $year, 404);

        $data = $request->validate([
            'rfq_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:rfq_date'],
            'purchase_requisition_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(['draft', 'open', 'awarded', 'cancelled'])],
            'narration' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_name' => ['required', 'string', 'max:200'],
            'lines.*.product_id' => ['nullable', 'integer'],
            'lines.*.quantity' => ['required'],
            'lines.*.unit' => ['nullable', 'string', 'max:40'],
        ]);

        $rfq = $service->save($company, $branch, $year, $request->user(), $data);

        return redirect()->to(tally_route('books.tally.rfqs.show', $rfq))
            ->with('status', 'RFQ saved.');
    }

    public function show(WorkspaceContext $context, RequestForQuotation $rfq, PartyDirectory $parties): View
    {
        abort_unless($context->company() && $rfq->company_id === $context->company()->id, 404);
        $rfq->load(['lines', 'vendorQuotations.supplier', 'vendorQuotations.lines']);

        return view('tally::rfqs.show', [
            'company' => $context->company(),
            'rfq' => $rfq,
            'suppliers' => $parties->options($context->company(), InvoiceKind::Purchase),
        ]);
    }

    public function storeVendorQuote(Request $request, WorkspaceContext $context, RequestForQuotation $rfq, RfqService $service): RedirectResponse
    {
        abort_unless($context->company() && $rfq->company_id === $context->company()->id, 404);

        $data = $request->validate([
            'supplier_ledger_id' => ['required', 'integer'],
            'quote_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date'],
            'lead_days' => ['nullable', 'integer', 'min:0'],
            'payment_terms_note' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_name' => ['required', 'string', 'max:200'],
            'lines.*.quantity' => ['required'],
            'lines.*.rate' => ['required'],
            'lines.*.tax_rate' => ['nullable'],
            'lines.*.product_id' => ['nullable', 'integer'],
        ]);

        $service->saveVendorQuote($context->company(), $rfq, $data);

        return redirect()->to(tally_route('books.tally.rfqs.show', $rfq))
            ->with('status', 'Vendor quotation saved.');
    }

    public function selectVendor(Request $request, WorkspaceContext $context, RequestForQuotation $rfq, VendorQuotation $vendorQuotation, RfqService $service): RedirectResponse
    {
        abort_unless($context->company() && $rfq->company_id === $context->company()->id, 404);

        $service->selectWinner($context->company(), $rfq, $vendorQuotation);

        return redirect()->to(tally_route('books.tally.rfqs.show', $rfq))
            ->with('status', 'Vendor selected.');
    }

    public function convertToPurchaseOrder(Request $request, WorkspaceContext $context, RequestForQuotation $rfq, RfqService $service): RedirectResponse
    {
        $company = $context->company();
        $branch = $context->branch();
        $year = $context->financialYear();
        abort_unless($company && $branch && $year && $rfq->company_id === $company->id, 404);

        $order = $service->convertToPurchaseOrder($company, $branch, $year, $request->user(), $rfq);

        return redirect()->to(tally_route('books.tally.purchase-orders.show', $order))
            ->with('status', 'Purchase order created from selected vendor quote.');
    }
}
