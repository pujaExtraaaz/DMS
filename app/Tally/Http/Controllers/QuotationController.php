<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Invoicing\PartyDirectory;
use Tally\Models\Party;
use Tally\Models\Quotation;
use Tally\Selling\QuotationService;

class QuotationController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', ['title' => 'Quotations', 'message' => 'Select a company first.']);
        }

        return view('tally::quotations.index', [
            'company' => $company,
            'year' => $context->financialYear(),
            'quotations' => Quotation::query()
                ->with('customer')
                ->where('company_id', $company->id)
                ->when($context->financialYear(), fn ($q) => $q->where('financial_year_id', $context->financialYear()->id))
                ->latest('quote_date')
                ->paginate(25),
        ]);
    }

    public function create(WorkspaceContext $context, PartyDirectory $parties): View
    {
        $company = $context->company();
        $branch = $context->branch();
        $year = $context->financialYear();
        abort_unless($company && $branch && $year, 404);

        return view('tally::quotations.form', [
            'company' => $company,
            'quotation' => new Quotation(['quote_date' => now()->toDateString(), 'status' => 'draft']),
            'partyLedgers' => $parties->options($company, \Tally\Invoicing\InvoiceKind::Sales),
            'addresses' => collect(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, QuotationService $service): RedirectResponse
    {
        $company = $context->company();
        $branch = $context->branch();
        $year = $context->financialYear();
        abort_unless($company && $branch && $year, 404);

        $data = $this->validated($request);
        $quote = $service->save($company, $branch, $year, $request->user(), $data);

        return redirect()->to(tally_route('books.tally.quotations.show', $quote))
            ->with('status', 'Quotation saved.');
    }

    public function show(WorkspaceContext $context, Quotation $quotation): View
    {
        abort_unless($context->company() && $quotation->company_id === $context->company()->id, 404);
        $quotation->load(['lines', 'customer', 'salesOrder']);

        return view('tally::quotations.show', [
            'company' => $context->company(),
            'quotation' => $quotation,
        ]);
    }

    public function edit(WorkspaceContext $context, Quotation $quotation, PartyDirectory $parties): View
    {
        abort_unless($context->company() && $quotation->company_id === $context->company()->id, 404);
        $quotation->load('lines');
        $party = Party::query()->where('ledger_id', $quotation->customer_ledger_id)->first();

        return view('tally::quotations.form', [
            'company' => $context->company(),
            'quotation' => $quotation,
            'partyLedgers' => $parties->options($context->company(), \Tally\Invoicing\InvoiceKind::Sales),
            'addresses' => $party?->addresses()->where('is_active', true)->get() ?? collect(),
        ]);
    }

    public function update(Request $request, WorkspaceContext $context, Quotation $quotation, QuotationService $service): RedirectResponse
    {
        abort_unless($context->company() && $quotation->company_id === $context->company()->id, 404);
        $quote = $service->save(
            $context->company(),
            $context->branch() ?? $quotation->branch,
            $context->financialYear() ?? $quotation->financialYear,
            $request->user(),
            $this->validated($request),
            $quotation
        );

        return redirect()->to(tally_route('books.tally.quotations.show', $quote))
            ->with('status', 'Quotation updated.');
    }

    public function convert(Request $request, WorkspaceContext $context, Quotation $quotation, QuotationService $service): RedirectResponse
    {
        abort_unless($context->company() && $quotation->company_id === $context->company()->id, 404);
        $order = $service->convertToSalesOrder($quotation, $request->user());

        return redirect()->to(tally_route('books.tally.sales-orders.show', $order))
            ->with('status', 'Converted to sales order '.$order->number.'.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'quote_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:quote_date'],
            'customer_ledger_id' => ['required', 'integer'],
            'billing_address_id' => ['nullable', 'integer'],
            'shipping_address_id' => ['nullable', 'integer'],
            'reference_number' => ['nullable', 'string', 'max:80'],
            'narration' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', Rule::in(['draft', 'sent', 'accepted', 'expired', 'cancelled'])],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_name' => ['required', 'string', 'max:200'],
            'lines.*.product_id' => ['nullable', 'integer'],
            'lines.*.quantity' => ['required'],
            'lines.*.rate' => ['required'],
            'lines.*.discount' => ['nullable'],
            'lines.*.tax_amount' => ['nullable'],
        ]);
    }
}
