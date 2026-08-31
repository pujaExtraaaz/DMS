<?php

namespace App\Http\Controllers\Sales;

use App\Domains\Inventory\Services\StockMovementService;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Master\Services\PriceMasterService;
use App\Domains\Payment\Services\OutstandingLedgerService;
use App\Domains\Sales\Models\EInvoice;
use App\Domains\Sales\Models\EWayBill;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Domains\Sales\Services\InvoiceNumberGenerator;
use App\Http\Controllers\Controller;
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
        protected StockMovementService $stockMovementService,
        protected OutstandingLedgerService $outstandingLedgerService,
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
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            $invoice = DB::transaction(function () use ($validated) {
                $subtotal = 0;
                $taxAmount = 0;

                foreach ($validated['items'] as $item) {
                    $lineTotal = $item['quantity'] * $item['unit_price'];
                    $subtotal += $lineTotal;
                    $product = Product::find($item['product_id']);
                    $taxAmount += $lineTotal * ((float) $product->tax_rate / 100);
                }

                $discount = (float) ($validated['discount_amount'] ?? 0);
                $grandTotal = $subtotal - $discount + $taxAmount;

                $invoice = Invoice::create([
                    'invoice_no' => $this->invoiceNumberGenerator->generate(),
                    'customer_id' => $validated['customer_id'],
                    'salesperson_id' => auth()->id(),
                    'invoice_date' => $validated['invoice_date'],
                    'status' => 'issued',
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'tax_amount' => $taxAmount,
                    'grand_total' => $grandTotal,
                    'paid_amount' => 0,
                    'notes' => $validated['notes'] ?? null,
                ]);

                foreach ($validated['items'] as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $uom = Uom::findOrFail($item['uom_id']);
                    $lineTotal = $item['quantity'] * $item['unit_price'];
                    $lineTax = $lineTotal * ((float) $product->tax_rate / 100);

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $item['product_id'],
                        'uom_id' => $item['uom_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'discount_amount' => 0,
                        'tax_amount' => $lineTax,
                        'line_total' => $lineTotal,
                    ]);

                    $this->stockMovementService->recordOut(
                        product: $product,
                        uom: $uom,
                        quantity: (float) $item['quantity'],
                        type: 'sale',
                        reference: $invoice,
                        notes: "Direct invoice {$invoice->invoice_no}",
                        user: auth()->user(),
                    );
                }

                $this->outstandingLedgerService->recordInvoice($invoice);

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

    public function pdf(Invoice $invoice): Response
    {
        $invoice->load(['customer', 'items.product', 'items.uom']);

        $html = view('sales.invoices.pdf', compact('invoice'))->render();

        return response($html, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'inline; filename="'.$invoice->invoice_no.'.html"',
        ]);
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

        return $this->flashSuccess('E-Invoice stub generated.');
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

        return $this->flashSuccess('E-Way bill stub generated.');
    }
}
