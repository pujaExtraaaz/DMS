<?php

namespace Tally\Http\Controllers;

use Tally\Accounting\VoucherStatus;
use Tally\Context\WorkspaceContext;
use Tally\Documents\DocumentPdf;
use Tally\Documents\TradingDocument;
use Tally\Models\PriceList;
use Tally\Http\Requests\InvoiceRequest;
use Tally\Invoicing\InvoiceKind;
use Tally\Invoicing\InvoiceNumberer;
use Tally\Invoicing\InvoiceService;
use Tally\Invoicing\PartyDirectory;
use Tally\Invoicing\TradingAccounts;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Tally\Models\Invoice;
use Tally\Models\InvoiceLine;
use Tally\Support\Queries\DateRange;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Tally\Tax\SupplyType;

class InvoiceController extends Controller
{
    public function index(Request $request, WorkspaceContext $context): View
    {
        $kind = $this->kind($request);
        $scope = $this->scope($context, $kind);

        if ($scope instanceof View) {
            return $scope;
        }

        [$company, $branch, $year] = $scope;
        $search = trim($request->string('q')->toString());
        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';
        $branches = $company->branches()->where('is_active', true)->orderBy('name')->get();
        $branchFilter = (string) $request->input('branch_id', $branch->id);

        if ($branchFilter !== 'all' && ! $branches->contains(fn (Branch $row) => (string) $row->id === $branchFilter)) {
            $branchFilter = (string) $branch->id;
        }

        $invoices = Invoice::query()
            ->with(['party', 'branch'])
            ->where('company_id', $company->id)
            ->where('financial_year_id', $year->id)
            ->where('kind', $kind)
            ->when($branchFilter !== 'all', fn ($query) => $query->where('branch_id', $branchFilter))
            ->when($search !== '', function ($query) use ($like) {
                $query->where(function ($query) use ($like) {
                    $query->where('invoice_number', 'like', $like)
                        ->orWhere('narration', 'like', $like)
                        ->orWhere('reference_number', 'like', $like)
                        ->orWhereHas('party', fn ($party) => $party->where('name', 'like', $like));
                });
            })
            ->tap(fn ($query) => DateRange::apply($query, 'invoice_date', $this->dateFilter($request->input('from')), $this->dateFilter($request->input('to'))))
            ->when(
                in_array($request->input('status'), array_column(VoucherStatus::cases(), 'value'), true),
                fn ($query) => $query->where('status', $request->input('status'))
            )
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('tally::invoices.index', [
            'kind' => $kind,
            'company' => $company,
            'branch' => $branch,
            'year' => $year,
            'branches' => $branches,
            'invoices' => $invoices,
            'filters' => [
                'q' => $search,
                'from' => $request->input('from'),
                'to' => $request->input('to'),
                'status' => $request->input('status'),
                'branch_id' => $branchFilter,
            ],
        ]);
    }

    public function create(
        Request $request,
        WorkspaceContext $context,
        InvoiceNumberer $numbers,
        PartyDirectory $parties,
        TradingAccounts $accounts,
    ): View {
        $kind = $this->kind($request);
        $scope = $this->scope($context, $kind);

        if ($scope instanceof View) {
            return $scope;
        }

        [$company, $branch, $year] = $scope;
        $order = null;

        if ($kind === InvoiceKind::Sales && $request->filled('sales_order')) {
            $order = \Tally\Models\SalesOrder::query()
                ->with('lines')
                ->where('company_id', $company->id)
                ->where('financial_year_id', $year->id)
                ->whereKey($request->integer('sales_order'))
                ->first();
        }

        $lines = [[
            'item_name' => '',
            'product_id' => '',
            'godown_id' => '',
            'tax_rate_id' => '',
            'quantity' => '',
            'rate' => '',
            'discount' => '',
            'tax_amount' => '',
        ]];

        if ($order) {
            $lines = $order->lines
                ->filter(fn ($line) => (float) $line->quantity - (float) $line->fulfilled_quantity - (float) $line->cancelled_quantity > 0)
                ->map(fn ($line) => [
                    'item_name' => $line->item_name,
                    'product_id' => $line->product_id,
                    'godown_id' => '',
                    'tax_rate_id' => $line->tax_rate_id,
                    'quantity' => number_format((float) $line->quantity - (float) $line->fulfilled_quantity - (float) $line->cancelled_quantity, 4, '.', ''),
                    'rate' => $line->rate,
                    'discount' => $line->discount,
                    'tax_amount' => '',
                ])
                ->values()
                ->all();
        }

        return view('tally::invoices.form', [
            'title' => 'New '.$kind->documentLabel(),
            'kind' => $kind,
            'company' => $company,
            'branch' => $branch,
            'year' => $year,
            'action' => tally_route($kind->routeName('store')),
            'method' => 'POST',
            'invoice' => new Invoice([
                'kind' => $kind,
                'invoice_date' => $this->defaultDate($year),
                'status' => VoucherStatus::Draft,
                'party_ledger_id' => $order?->customer_ledger_id,
                'sales_order_id' => $order?->id,
                'narration' => $order?->narration,
            ]),
            'lines' => $lines,
            'partyLedgers' => $parties->options($company, $kind),
            'accountLedgers' => $accounts->options($company, $kind),
            'products' => $company->products()->where('is_active', true)->orderBy('name')->get(),
            'godowns' => $company->godowns()->where('is_active', true)->orderBy('name')->get(),
            'taxRates' => $company->taxRates()->where('is_active', true)->orderBy('name')->get(),
            'hsnSacs' => $company->hsnSacs()->where('is_active', true)->orderBy('code')->get(),
            'priceLists' => $this->priceLists($company),
            'availableCredit' => $this->availableCreditFor($order?->customer_ledger_id ? \Tally\Models\Ledger::query()->find($order->customer_ledger_id) : null),
            'nextNumber' => $numbers->peek($company, $branch, $year, $kind),
        ]);
    }

    public function store(InvoiceRequest $request, WorkspaceContext $context, InvoiceService $invoices): RedirectResponse
    {
        $kind = $this->kind($request);
        $invoice = $invoices->save(
            $context->company(),
            $context->branch(),
            $context->financialYear(),
            $request->user(),
            $kind,
            $request->validated(),
            $request->input('action') === 'post',
        );

        return tally_redirect($kind->routeName('show'), $invoice)
            ->with('status', $invoice->isPosted() ? $kind->documentLabel().' posted.' : $kind->documentLabel().' saved as draft.');
    }

    public function show(Request $request, Invoice $invoice): View
    {
        $this->guardKind($request, $invoice);
        $invoice->load(['lines.product', 'lines.godown', 'party', 'account', 'voucher.entries.ledger', 'creator', 'branch']);

        return view('tally::invoices.show', [
            'kind' => $invoice->kind,
            'invoice' => $invoice,
        ]);
    }

    public function einvoice(Request $request, Invoice $invoice): View
    {
        $this->guardKind($request, $invoice);

        return view('tally::invoices.e-invoice', $this->statutoryDocument($invoice));
    }

    public function eway(Request $request, Invoice $invoice): View
    {
        $this->guardKind($request, $invoice);

        return view('tally::invoices.e-way-bill', $this->statutoryDocument($invoice));
    }

    public function print(Request $request, Invoice $invoice, TradingDocument $documents): View
    {
        $this->guardKind($request, $invoice);

        return view('tally::documents.print', [
            'document' => $this->withWords($invoice->company, $documents->invoice($invoice)),
            'back' => tally_route($invoice->kind->routeName('show'), $invoice),
        ]);
    }

    public function pdf(Request $request, Invoice $invoice, TradingDocument $documents, DocumentPdf $pdf): \Symfony\Component\HttpFoundation\Response
    {
        $this->guardKind($request, $invoice);

        return $pdf->trading($this->withWords($invoice->company, $documents->invoice($invoice)));
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    private function withWords(\Tally\Models\Company $company, array $document): array
    {
        if (! app(\Tally\Preferences\PreferenceStore::class)->enabled($company, 'print.amount_in_words')) {
            $document['words'] = null;
        }

        return $document;
    }

    public function edit(
        Request $request,
        WorkspaceContext $context,
        PartyDirectory $parties,
        TradingAccounts $accounts,
        Invoice $invoice,
    ): View|RedirectResponse {
        $this->guardKind($request, $invoice);

        if ($invoice->isCancelled()) {
            app(\Tally\Audit\AuditLogger::class)->record('change_blocked', 'accounting', $invoice, 'A cancelled invoice cannot be changed.');

            return tally_redirect($invoice->kind->routeName('show'), $invoice)
                ->with('error', 'A cancelled invoice cannot be changed.');
        }

        $kind = $invoice->kind;
        $scope = $this->scope($context, $kind);

        if ($scope instanceof View) {
            return $scope;
        }

        [$company, $branch, $year] = $scope;
        $invoice->load('lines');

        return view('tally::invoices.form', [
            'title' => 'Edit '.$invoice->invoice_number,
            'kind' => $kind,
            'company' => $company,
            'branch' => $branch,
            'year' => $year,
            'action' => tally_route($kind->routeName('update'), $invoice),
            'method' => 'PUT',
            'invoice' => $invoice,
            'lines' => $invoice->lines->map(fn (InvoiceLine $line) => [
                'item_name' => $line->item_name,
                'product_id' => $line->product_id,
                'godown_id' => $line->godown_id,
                'tax_rate_id' => $line->tax_rate_id,
                'quantity' => $this->trimNumber((string) $line->quantity),
                'rate' => $this->trimNumber((string) $line->rate),
                'discount' => $this->trimNumber((string) $line->discount),
                'tax_amount' => $this->trimNumber((string) $line->tax_amount),
                'hsn_sac_id' => $line->hsn_sac_id,
                'line_total' => $line->line_total,
            ])->all(),
            'partyLedgers' => $parties->options($company, $kind),
            'accountLedgers' => $accounts->options($company, $kind),
            'products' => $company->products()->where('is_active', true)->orderBy('name')->get(),
            'godowns' => $company->godowns()->where('is_active', true)->orderBy('name')->get(),
            'taxRates' => $company->taxRates()->where('is_active', true)->orderBy('name')->get(),
            'hsnSacs' => $company->hsnSacs()->where('is_active', true)->orderBy('code')->get(),
            'priceLists' => $this->priceLists($company),
            'availableCredit' => $this->availableCreditFor($invoice->party),
            'nextNumber' => $invoice->invoice_number,
        ]);
    }

    public function update(InvoiceRequest $request, WorkspaceContext $context, InvoiceService $invoices, Invoice $invoice): RedirectResponse
    {
        $this->guardKind($request, $invoice);
        $invoice = $invoice->isPosted()
            ? $invoices->alter($invoice, $request->user(), $request->validated(), $request->input('action') === 'post')
            : $invoices->save(
            $context->company(),
            $invoice->branch,
            $invoice->financialYear,
            $request->user(),
            $invoice->kind,
            $request->validated(),
            $request->input('action') === 'post',
            $invoice,
        );

        return tally_redirect($invoice->kind->routeName('show'), $invoice)
            ->with('status', $invoice->isPosted() ? $invoice->kind->documentLabel().' posted.' : 'Draft updated.');
    }

    public function post(Request $request, InvoiceService $invoices, Invoice $invoice): RedirectResponse
    {
        $this->guardKind($request, $invoice);
        $invoice = $invoices->post($invoice, $request->user(), $request->boolean('force_duplicate'));

        return tally_redirect($invoice->kind->routeName('show'), $invoice)
            ->with('status', $invoice->kind->documentLabel().' posted.');
    }

    public function cancel(Request $request, InvoiceService $invoices, Invoice $invoice): RedirectResponse
    {
        $this->guardKind($request, $invoice);
        $invoices->cancel($invoice);

        return tally_redirect($invoice->kind->routeName('show'), $invoice)
            ->with('status', $invoice->kind->documentLabel().' cancelled.');
    }

    public function convertProforma(Request $request, InvoiceService $invoices, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->kind === InvoiceKind::Proforma && $invoice->isDraft(), 404);
        $invoice->load('lines');

        $tax = $invoices->save(
            $invoice->company,
            $invoice->branch,
            $invoice->financialYear,
            $request->user(),
            InvoiceKind::Sales,
            [
                'invoice_date' => $invoice->invoice_date->toDateString(),
                'party_ledger_id' => $invoice->party_ledger_id,
                'account_ledger_id' => $invoice->account_ledger_id,
                'reference_number' => $invoice->invoice_number,
                'narration' => $invoice->narration,
                'invoice_category' => $invoice->invoice_category ?: 'tax_invoice',
                'billing_mode' => $invoice->billing_mode ?: 'goods',
                'billing_address_id' => $invoice->billing_address_id,
                'shipping_address_id' => $invoice->shipping_address_id,
                'converted_from_invoice_id' => $invoice->id,
                'sales_order_id' => $invoice->sales_order_id,
                'lines' => $invoice->lines->map(fn ($line) => [
                    'item_name' => $line->item_name,
                    'product_id' => $line->product_id,
                    'godown_id' => $line->godown_id,
                    'tax_rate_id' => $line->tax_rate_id,
                    'hsn_sac_id' => $line->hsn_sac_id,
                    'quantity' => $line->quantity,
                    'rate' => $line->rate,
                    'discount' => $line->discount,
                    'tax_amount' => $line->tax_amount,
                ])->all(),
            ],
            false
        );

        $invoice->update(['status' => \Tally\Accounting\VoucherStatus::Cancelled, 'cancelled_at' => now()]);

        return tally_redirect(InvoiceKind::Sales->routeName('show'), $tax)
            ->with('status', 'Pro forma converted to tax invoice '.$tax->invoice_number.'.');
    }

    private function kind(Request $request): InvoiceKind
    {
        $name = $request->route()?->getName() ?? '';

        foreach (InvoiceKind::cases() as $kind) {
            if (str_contains($name, 'invoices.'.$kind->slug().'.')) {
                return $kind;
            }
        }

        return InvoiceKind::Sales;
    }

    private function guardKind(Request $request, Invoice $invoice): void
    {
        abort_unless($invoice->kind === $this->kind($request), 404);
    }

    /**
     * @return array{0: Company, 1: Branch, 2: FinancialYear}|View
     */
    private function scope(WorkspaceContext $context, InvoiceKind $kind): array|View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => $kind->label(),
                'message' => $this->missingContextMessage($kind, false),
            ]);
        }

        if (! $context->branch() || ! $context->financialYear()) {
            return view('tally::workspace.needs-company', [
                'title' => $kind->label(),
                'message' => $this->missingContextMessage($kind, true),
            ]);
        }

        return [$company, $context->branch(), $context->financialYear()];
    }

    private function missingContextMessage(InvoiceKind $kind, bool $needsPeriod): string
    {
        $document = match ($kind) {
            InvoiceKind::Sales => 'Sales invoices',
            InvoiceKind::Purchase => 'Purchase invoices',
            default => 'a '.$kind->documentLabel(),
        };

        return $needsPeriod
            ? 'Select a branch and a financial year before entering '.$document.'.'
            : 'Select or create a company before entering '.$document.'.';
    }

    private function defaultDate(FinancialYear $year): string
    {
        $today = now()->toDateString();

        if ($today >= $year->start_date->toDateString() && $today <= $year->end_date->toDateString()) {
            return $today;
        }

        return $year->start_date->toDateString();
    }

    private function dateFilter(mixed $value): ?string
    {
        $value = trim((string) $value);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }

    /**
     * @return \Illuminate\Support\Collection<int, PriceList>
     */
    private function priceLists(Company $company)
    {
        return PriceList::query()->with('lines')->where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();
    }

    private function availableCreditFor(?\Tally\Models\Ledger $ledger): ?string
    {
        if (! $ledger) {
            return null;
        }

        return app(\Tally\Parties\PartyCredit::class)->availableCredit($ledger);
    }

    /**
     * @return array<string, mixed>
     */
    private function statutoryDocument(Invoice $invoice): array
    {
        $invoice->loadMissing(['company', 'party', 'lines.product', 'lines.hsnSac', 'lines.taxRate']);

        $company = $invoice->company;
        $ledger = $invoice->party;
        $party = $ledger
            ? \Tally\Models\Party::query()->where('ledger_id', $ledger->id)->first()
            : null;
        $purchase = in_array($invoice->kind, [InvoiceKind::Purchase, InvoiceKind::DebitNote], true);
        $companyParty = [
            'name' => $company->legal_name ?: $company->name,
            'gstin' => $company->gstin ?: '—',
            'address' => $company->address ?: '—',
            'state' => $company->state ?: '—',
            'pincode' => $company->pincode ?: '—',
            'phone' => $company->phone ?: $company->mobile,
        ];
        $counterParty = [
            'name' => $party?->legal_name ?: $ledger?->name ?: '—',
            'gstin' => $party?->gstin ?: '—',
            'address' => $party?->billing_address ?: $party?->shipping_address ?: '—',
            'state' => $party?->state ?: '—',
            'pincode' => '—',
            'phone' => $party?->phone ?: $party?->mobile,
        ];
        $tax = round((float) $invoice->tax_total, 2);
        $intra = $invoice->supply_type !== SupplyType::Inter;
        $cgst = $intra ? round($tax / 2, 2) : 0.0;
        $lines = [];
        foreach ($invoice->lines as $line) {
            $qty = (float) $line->quantity;
            $rate = (float) $line->rate;
            $discount = (float) $line->discount;
            $lineTax = (float) $line->tax_amount;
            $taxable = max(0, round(($qty * $rate) - $discount, 2));
            $gstRate = $line->taxRate
                ? (float) $line->taxRate->igst_rate ?: ((float) $line->taxRate->cgst_rate + (float) $line->taxRate->sgst_rate)
                : null;
            if ($gstRate === null && $taxable > 0) {
                $gstRate = round(($lineTax / $taxable) * 100, 2);
            }
            $lines[] = [
                'name' => $line->item_name,
                'hsn' => $line->hsnSac?->code ?: '—',
                'qty' => $this->trimNumber((string) $line->quantity),
                'rate' => number_format($rate, 2),
                'discount' => number_format($discount, 2),
                'taxable' => number_format($taxable, 2),
                'gst' => number_format((float) $gstRate, 2),
                'tax' => number_format($lineTax, 2),
                'total' => number_format((float) $line->line_total, 2),
            ];
        }

        return [
            'purchase' => $purchase,
            'documentTitle' => $invoice->kind === InvoiceKind::Sales
                ? 'Tax Invoice'
                : $invoice->kind->documentLabel(),
            'number' => $invoice->invoice_number,
            'date' => $invoice->invoice_date->format(app(\Tally\Preferences\PreferenceStore::class)->dateFormat($company, request()->user())),
            'reference' => $invoice->reference_number,
            'place' => $invoice->place_of_supply,
            'supply' => $invoice->supply_type?->label(),
            'narration' => $invoice->narration,
            'seller' => $purchase ? $counterParty : $companyParty,
            'buyer' => $purchase ? $companyParty : $counterParty,
            'lines' => $lines,
            'subtotal' => number_format((float) $invoice->subtotal, 2),
            'discount' => number_format((float) $invoice->discount_total, 2),
            'cgst' => number_format($cgst, 2),
            'sgst' => number_format($intra ? round($tax - $cgst, 2) : 0, 2),
            'igst' => number_format($intra ? 0 : $tax, 2),
            'tax' => number_format($tax, 2),
            'grand' => number_format((float) $invoice->grand_total, 2),
            'currencySymbol' => $company->currency_symbol ?: '₹',
            'currencyName' => $company->currency_formal_name ?: 'INR',
            'words' => app(\Tally\Preferences\PreferenceStore::class)->enabled($company, 'print.amount_in_words')
                ? \Tally\Support\IndianCurrency::words((float) $invoice->grand_total, \Tally\Support\IndianCurrency::majorUnit($company->currency_formal_name))
                : null,
            'irn' => 'MANUAL-'.strtoupper(substr(hash('sha256', $invoice->invoice_number), 0, 16)),
            'ewayNo' => 'MANUAL-'.strtoupper(substr(hash('sha256', $invoice->invoice_number.'eway'), 0, 12)),
            'back' => tally_route($invoice->kind->routeName('show'), $invoice),
            'terms' => $purchase ? ($company->purchase_terms ?: null) : ($company->sales_terms ?: null),
        ];
    }

    private function trimNumber(string $value): string
    {
        if (! str_contains($value, '.')) {
            return $value;
        }

        $trimmed = rtrim(rtrim($value, '0'), '.');

        return $trimmed === '' ? '0' : $trimmed;
    }
}
