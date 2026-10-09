<?php

namespace Tally\Http\Controllers;

use Tally\Accounting\VoucherStatus;
use Tally\Context\WorkspaceContext;
use Tally\Documents\DocumentPdf;
use Tally\Documents\TradingDocument;
use Tally\Models\PriceList;
use Tally\Tax\GstReturnFile;
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
use Symfony\Component\HttpFoundation\Response;

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

        return redirect()
            ->route($kind->routeName('show'), $invoice)
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

    public function einvoice(Request $request, Invoice $invoice, GstReturnFile $files): Response
    {
        $this->guardKind($request, $invoice);

        return $this->jsonDownload($files->einvoice($invoice), 'einvoice-'.$invoice->invoice_number.'.json');
    }

    public function eway(Request $request, Invoice $invoice, GstReturnFile $files): Response
    {
        $this->guardKind($request, $invoice);

        return $this->jsonDownload($files->eway($invoice), 'eway-'.$invoice->invoice_number.'.json');
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

            return redirect()
                ->route($invoice->kind->routeName('show'), $invoice)
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

        return redirect()
            ->route($invoice->kind->routeName('show'), $invoice)
            ->with('status', $invoice->isPosted() ? $invoice->kind->documentLabel().' posted.' : 'Draft updated.');
    }

    public function post(Request $request, InvoiceService $invoices, Invoice $invoice): RedirectResponse
    {
        $this->guardKind($request, $invoice);
        $invoice = $invoices->post($invoice, $request->user());

        return redirect()
            ->route($invoice->kind->routeName('show'), $invoice)
            ->with('status', $invoice->kind->documentLabel().' posted.');
    }

    public function cancel(Request $request, InvoiceService $invoices, Invoice $invoice): RedirectResponse
    {
        $this->guardKind($request, $invoice);
        $invoices->cancel($invoice);

        return redirect()
            ->route($invoice->kind->routeName('show'), $invoice)
            ->with('status', $invoice->kind->documentLabel().' cancelled.');
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

    /**
     * @param  array<string, mixed>  $payload
     */
    private function jsonDownload(array $payload, string $filename): Response
    {
        return response(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
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
