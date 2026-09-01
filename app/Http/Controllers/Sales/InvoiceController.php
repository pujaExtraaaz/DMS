<?php

namespace App\Http\Controllers\Sales;

use App\Domains\Inventory\Services\StockMovementService;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Master\Services\PriceMasterService;
use App\Domains\Master\Services\SalePricingService;
use App\Domains\Payment\Services\PaymentLinkService;
use App\Domains\Payment\Services\OutstandingLedgerService;
use App\Domains\Sales\Models\EInvoice;
use App\Domains\Sales\Models\EWayBill;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Domains\Sales\Services\InvoiceNumberGenerator;
use App\Http\Controllers\Controller;
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
        return view('sales.invoices.create', [
            'customers' => Customer::with('customerType')->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::with('baseUom')->where('is_active', true)->orderBy('name')->get(),
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_date' => 'required|date',
            'notes' => 'nullable|string',
            'discount_amount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'nullable|numeric|min:0',
        ]);

        try {
            $invoice = DB::transaction(function () use ($validated) {
                $customer = Customer::findOrFail($validated['customer_id']);
                $pricing = $this->salePricingService->price($customer, $validated['items'], (float) ($validated['discount_amount'] ?? 0));

                $invoice = Invoice::create([
                    'invoice_no' => $this->invoiceNumberGenerator->generate(),
                    'customer_id' => $validated['customer_id'],
                    'salesperson_id' => auth()->id(),
                    'invoice_date' => $validated['invoice_date'],
                    'status' => 'issued',
                    'subtotal' => $pricing['subtotal'],
                    'discount_amount' => $pricing['discount'],
                    'tax_amount' => $pricing['tax'],
                    'grand_total' => $pricing['total'],
                    'paid_amount' => 0,
                    'notes' => $validated['notes'] ?? null,
                ]);

                foreach ($pricing['lines'] as $line) {
                    $product = $line['product'];
                    $uom = $line['uom'];

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $product->id,
                        'uom_id' => $uom->id,
                        'quantity' => $line['quantity'],
                        'unit_price' => $line['unitPrice'],
                        'discount_amount' => $line['discount'],
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

        $pdf = Pdf::loadView('sales.invoices.pdf', compact('invoice'))->setPaper('a4');

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

        return view('sales.invoices.pdf', compact('invoice'));
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
        EInvoice::updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'status' => 'manual',
                'irn' => 'STUB-IRN-'.strtoupper(substr(md5($invoice->invoice_no), 0, 12)),
                'payload' => ['stub' => true, 'generated_at' => now()->toIso8601String()],
            ]
        );

        return redirect()->route('invoices.e-invoice.document', $invoice)
            ->with('status', 'E-Invoice generated successfully.');
    }

    public function generateEway(Invoice $invoice): RedirectResponse
    {
        EWayBill::updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'status' => 'manual',
                'eway_bill_no' => 'STUB-EWB-'.strtoupper(substr(md5($invoice->invoice_no.'eway'), 0, 10)),
                'payload' => ['stub' => true, 'generated_at' => now()->toIso8601String()],
            ]
        );

        return redirect()->route('invoices.eway.document', $invoice)
            ->with('status', 'E-Way Bill generated successfully.');
    }
}
