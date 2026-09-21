<?php

namespace App\Http\Controllers\Sales;

use App\Domains\Inventory\Services\StockMovementService;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Master\Services\PriceMasterService;
use App\Domains\Master\Services\ProductDiscountService;
use App\Domains\Master\Services\SalePricingService;
use App\Domains\Organization\Services\FinancialYearService;
use App\Domains\Organization\Models\Company;
use App\Domains\Payment\Services\PaymentLinkService;
use App\Domains\Payment\Services\OutstandingLedgerService;
use App\Domains\Sales\Models\EInvoice;
use App\Domains\Sales\Models\EWayBill;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Domains\Sales\Services\InvoiceNumberGenerator;
use App\Domains\Sales\Services\MastersIndiaGspService;
use App\Http\Controllers\Controller;
use App\Support\DueDateService;
use App\Support\QrCodeRenderer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceNumberGenerator $invoiceNumberGenerator,
        protected PriceMasterService $priceMasterService,
        protected SalePricingService $salePricingService,
        protected StockMovementService $stockMovementService,
        protected OutstandingLedgerService $outstandingLedgerService,
        protected PaymentLinkService $paymentLinkService,
        protected DueDateService $dueDateService,
        protected ProductDiscountService $productDiscountService,
        protected MastersIndiaGspService $mastersIndiaService,
        protected FinancialYearService $financialYearService,
    ) {}

    public function index(Request $request): View
    {
        $invoices = Invoice::query()
            ->with(['customer', 'salesperson'])
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('invoice_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('invoice_date', '<=', $request->date_to))
            ->latest('invoice_date')
            ->paginate(15)
            ->withQueryString();

        return view('sales.invoices.index', [
            'invoices' => $invoices,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $company = Company::query()->find(
            auth()->user()?->company_id
        ) ?? Company::query()->first();

        return view('sales.invoices.create', [
            'customers' => Customer::with('customerType')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),

            'products' => Product::with('baseUom')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),

            'uoms' => Uom::where('is_active', true)
                ->orderBy('name')
                ->get(),

            // NEW
            'sellingTermsAndConditions' => $company?->selling_terms_and_conditions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_date' => 'required|date',
            'notes' => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
            'vehicle_no' => 'nullable|string|max:40',
            'transport_mode' => 'nullable|string|max:30',
            'reference_no' => 'nullable|string|max:60',
            'delivery_state' => 'nullable|string|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'universal_discount_type' => 'nullable|in:percent,flat',
            'universal_discount_value' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.discount_type' => 'nullable|in:percent,flat',
            'items.*.discount_value' => 'nullable|numeric|min:0',
            'items.*.batch_no' => 'nullable|string|max:60',
        ]);

        // Enforce FY period lock — refuse to post into a closed/unmapped period.
        $this->financialYearService->assertOpen($validated['invoice_date']);

        try {
            $invoice = DB::transaction(function () use ($validated) {
                $customer = Customer::findOrFail($validated['customer_id']);
                $pricing = $this->salePricingService->price($customer, $validated['items'], (float) ($validated['discount_amount'] ?? 0));
                $due = $this->dueDateService->forSalesInvoice($customer, $validated['invoice_date']);

                // Universal (header-level) discount on top of any item-level discounts already reduced in $pricing['subtotal'].
                $universalType = $validated['universal_discount_type'] ?? 'flat';
                $universalValue = (float) ($validated['universal_discount_value'] ?? 0);
                $universalDiscount = $this->productDiscountService->universalDiscount((float) $pricing['subtotal'], $universalType, $universalValue);

                // The pricing service returns 'discount' as the item-level total; grand total gets universal deducted on top.
                $itemDiscountTotal = (float) $pricing['discount'];
                $adjustedTotal = round(max(0, (float) $pricing['total'] - $universalDiscount), 2);
                $totalDiscount = round($itemDiscountTotal + $universalDiscount, 2);

                $invoice = Invoice::create([
                    'invoice_no' => $this->invoiceNumberGenerator->generate(),
                    'customer_id' => $validated['customer_id'],
                    'salesperson_id' => auth()->id(),
                    'invoice_date' => $validated['invoice_date'],
                    'due_date' => $due['due_date'],
                    'due_date_basis' => $due['due_date_basis'],
                    'due_date_source_date' => $due['due_date_source_date'],
                    'payment_terms' => $customer->payment_terms,
                    'credit_days' => $due['credit_days'],
                    'status' => 'issued',
                    'subtotal' => $pricing['subtotal'],
                    'discount_amount' => $totalDiscount,
                    'universal_discount_type' => $universalType,
                    'universal_discount_value' => $universalValue,
                    'item_discount_total' => $itemDiscountTotal,
                    'tax_amount' => $pricing['tax'],
                    'grand_total' => $adjustedTotal,
                    'paid_amount' => 0,
                    'notes' => $validated['notes'] ?? null,
                    'terms_and_conditions' => $validated['terms_and_conditions'] ?? null,
                    'vehicle_no' => $validated['vehicle_no'] ?? null,
                    'transport_mode' => $validated['transport_mode'] ?? null,
                    'reference_no' => $validated['reference_no'] ?? null,
                    'delivery_state' => $validated['delivery_state'] ?? $customer->shipping_state ?? $customer->state,
                ]);

                foreach ($pricing['lines'] as $idx => $line) {
                    $product = $line['product'];
                    $uom = $line['uom'];
                    $inputRow = $validated['items'][$idx] ?? [];

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $product->id,
                        'uom_id' => $uom->id,
                        'quantity' => $line['quantity'],
                        'unit_price' => $line['unitPrice'],
                        'discount_amount' => $line['discount'],
                        'discount_type' => $inputRow['discount_type'] ?? 'flat',
                        'discount_value' => (float) ($inputRow['discount_value'] ?? $line['discount']),
                        'hsn_code' => $product->hsn_code,
                        'batch_no' => $inputRow['batch_no'] ?? null,
                        'tax_amount' => $line['tax'],
                        'line_total' => $line['lineTotal'],
                    ]);

                    $this->stockMovementService->recordOut(
                        product: $product,
                        uom: $uom,
                        quantity: (float) $line['quantity'],
                        type: 'sale',
                        reference: $invoice,
                        notes: "Direct invoice {$invoice->invoice_no}",
                        user: auth()->user(),
                    );
                }

                $this->outstandingLedgerService->recordInvoice($invoice);
                $this->paymentLinkService->createForInvoice($invoice);

                return $invoice;
            });
        } catch (\InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Invoice created successfully.', 'invoices.show', ['invoice' => $invoice]);
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['customer', 'salesperson', 'items.product', 'items.uom', 'payments', 'eInvoice', 'eWayBill', 'order']);

        return view('sales.invoices.show', compact('invoice'));
    }

    public function pdf(Request $request, Invoice $invoice): Response
    {
        $invoice->load(['customer', 'items.product', 'items.uom', 'eInvoice']);

        $company = \App\Domains\Organization\Models\Company::query()->first();

        // Prefer the signed QR from Masters India (base64 → PNG QR). Fall back to
        // a deterministic invoice QR so the PDF always shows something scannable.
       $invoiceUrl = route('invoice.qr', [
            'type' => 'sales',
            'token' => $invoice->qr_token,
        ]);

        $invoiceQrDataUri = QrCodeRenderer::dataUri($invoiceUrl, 180);

        $upiQrDataUri = null;
        if ($company && filled($company->upi_id)) {
            $upiUri = QrCodeRenderer::upiIntent($company->upi_id, $company->name, (float) $invoice->grand_total, 'Inv '.$invoice->invoice_no, $invoice->invoice_no);
            $upiQrDataUri = QrCodeRenderer::dataUri($upiUri, 160);
        }

        $pdf = Pdf::loadView('sales.invoices.pdf', [
            'invoice' => $invoice,
            'company' => $company,
            'invoiceQrDataUri' => $invoiceQrDataUri,
            'upiQrDataUri' => $upiQrDataUri,
        ])->setPaper('a4');

        return $request->boolean('download')
            ? $pdf->download($invoice->invoice_no.'.pdf')
            : $pdf->stream($invoice->invoice_no.'.pdf');
    }

    public function eInvoiceDocument(Invoice $invoice): View
    {
        $invoice->load(['customer', 'items.product', 'items.uom', 'eInvoice']);
        $eInvoice = $invoice->eInvoice ?? EInvoice::create([
            'invoice_id' => $invoice->id,
            'status' => 'manual',
            'irn' => 'MANUAL-'.strtoupper(substr(hash('sha256', $invoice->invoice_no), 0, 16)),
            'payload' => ['generated_at' => now()->toIso8601String()],
        ]);

        return view('sales.invoices.e-invoice', compact('invoice', 'eInvoice'));
    }

    public function eInvoicePreview(Invoice $invoice): View
    {
        $invoice->load(['customer', 'items.product', 'items.uom', 'eInvoice']);

        $company = \App\Domains\Organization\Models\Company::query()->first();

        $invoiceUrl = route('invoice.qr', [
            'type' => 'sales',
            'token' => $invoice->qr_token,
        ]);

        $invoiceQrDataUri = QrCodeRenderer::dataUri($invoiceUrl, 180);

        $upiQrDataUri = null;

        if ($company && filled($company->upi_id)) {
            $upiUri = QrCodeRenderer::upiIntent(
                $company->upi_id,
                $company->name,
                (float) $invoice->grand_total,
                'Inv '.$invoice->invoice_no,
                $invoice->invoice_no
            );

            $upiQrDataUri = QrCodeRenderer::dataUri($upiUri, 160);
        }

        return view('sales.invoices.pdf', [
            'invoice' => $invoice,
            'company' => $company,
            'invoiceQrDataUri' => $invoiceQrDataUri,
            'upiQrDataUri' => $upiQrDataUri,
        ]);
    }

    public function eWayBillDocument(Invoice $invoice): View
    {
        $invoice->load(['customer', 'items.product', 'items.uom', 'eWayBill']);
        $eWayBill = $invoice->eWayBill ?? EWayBill::create([
            'invoice_id' => $invoice->id,
            'status' => 'manual',
            'eway_bill_no' => 'MANUAL-'.strtoupper(substr(hash('sha256', $invoice->invoice_no.'eway'), 0, 12)),
            'payload' => ['generated_at' => now()->toIso8601String()],
        ]);

        return view('sales.invoices.e-way-bill', compact('invoice', 'eWayBill'));
    }

    public function generateEInvoice(Invoice $invoice): RedirectResponse
    {
        $invoice->load(['customer', 'items.product', 'items.uom']);

        try {
            $result = $this->mastersIndiaService->generateIrn($invoice);
        } catch (\Throwable $e) {
            return $this->flashError('E-Invoice generation failed: '.$e->getMessage());
        }

        EInvoice::updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'status' => $result['status'],
                'provider' => 'mastersindia',
                'irn' => $result['irn'],
                'ack_no' => $result['ack_no'] ?? null,
                'ack_date' => isset($result['ack_date']) ? \Carbon\Carbon::parse($result['ack_date']) : null,
                'signed_invoice' => $result['signed_invoice'] ?? null,
                'signed_qr_base64' => $result['signed_qr_base64'] ?? null,
                'requested_at' => now(),
                'payload' => $result['raw'],
            ]
        );

        $note = $result['status'] === 'stub'
            ? 'E-Invoice recorded with stub IRN (Masters India credentials missing).'
            : 'E-Invoice generated successfully.';

        return redirect()->route('invoices.e-invoice.document', $invoice)->with('status', $note);
    }

    public function generateEway(Invoice $invoice): RedirectResponse
    {
        $invoice->load(['customer', 'items.product', 'items.uom', 'eInvoice']);

        try {
            $result = $this->mastersIndiaService->generateEWayBill($invoice, array_filter([
                'VehNo' => $invoice->vehicle_no,
                'TransMode' => $invoice->transport_mode,
            ]));
        } catch (\Throwable $e) {
            return $this->flashError('E-Way Bill generation failed: '.$e->getMessage());
        }

        EWayBill::updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'status' => $result['status'],
                'provider' => 'mastersindia',
                'eway_bill_no' => $result['eway_bill_no'],
                'valid_upto' => isset($result['valid_upto']) ? \Carbon\Carbon::parse($result['valid_upto']) : null,
                'ewb_date' => isset($result['ewb_date']) ? \Carbon\Carbon::parse($result['ewb_date']) : null,
                'vehicle_no' => $invoice->vehicle_no,
                'transport_mode' => $invoice->transport_mode,
                'payload' => $result['raw'],
            ]
        );

        $note = $result['status'] === 'stub'
            ? 'E-Way Bill recorded with stub number (Masters India credentials missing).'
            : 'E-Way Bill generated successfully.';

        return redirect()->route('invoices.eway.document', $invoice)->with('status', $note);
    }
}
