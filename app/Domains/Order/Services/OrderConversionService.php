<?php

namespace App\Domains\Order\Services;

use App\Domains\Inventory\Services\StockMovementService;
use App\Domains\Order\Models\Order;
use App\Domains\Payment\Services\OutstandingLedgerService;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Domains\Sales\Services\InvoiceNumberGenerator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderConversionService
{
    public function __construct(
        protected InvoiceNumberGenerator $invoiceNumberGenerator,
        protected StockMovementService $stockMovementService,
        protected OutstandingLedgerService $outstandingLedgerService,
    ) {}

    public function convertToInvoice(Order $order): Invoice
    {
        if ($order->status === 'converted') {
            throw new InvalidArgumentException('Order has already been converted to an invoice.');
        }

        if (! in_array($order->status, ['approved', 'pending'], true)) {
            throw new InvalidArgumentException("Order status [{$order->status}] cannot be converted.");
        }

        return DB::transaction(function () use ($order) {
            $order->load('items.product', 'items.uom', 'customer');

            $invoice = Invoice::create([
                'invoice_no' => $this->invoiceNumberGenerator->generate(),
                'customer_id' => $order->customer_id,
                'order_id' => $order->id,
                'salesperson_id' => $order->salesperson_id,
                'invoice_date' => now()->toDateString(),
                'status' => 'issued',
                'subtotal' => $order->subtotal,
                'discount_amount' => $order->discount_amount,
                'tax_amount' => $order->tax_amount,
                'grand_total' => $order->grand_total,
                'paid_amount' => 0,
                'notes' => $order->notes,
            ]);

            foreach ($order->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item->product_id,
                    'uom_id' => $item->uom_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'line_total' => $item->line_total,
                ]);

                $this->stockMovementService->recordOut(
                    product: $item->product,
                    uom: $item->uom,
                    quantity: (float) $item->quantity,
                    type: 'sale',
                    reference: $invoice,
                    notes: "Sale from order {$order->order_no}",
                );
            }

            $order->update(['status' => 'converted']);

            $this->outstandingLedgerService->recordInvoice($invoice);

            return $invoice->load('items');
        });
    }
}
