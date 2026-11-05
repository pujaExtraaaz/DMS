<?php

namespace App\Domains\Order\Services;

use App\Domains\Inventory\Services\StockMovementService;
use App\Domains\Purchasing\Services\SerialBatchService;
use App\Domains\Master\Services\UomConversionService;
use App\Domains\Order\Models\Order;
use App\Domains\Payment\Services\OutstandingLedgerService;
use App\Domains\Payment\Services\PaymentLinkService;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Domains\Sales\Services\InvoiceNumberGenerator;
use App\Models\User;
use App\Support\AuditLogService;
use App\Support\DueDateService;
use App\Support\WarrantyPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderConversionService
{
    public function __construct(
        protected InvoiceNumberGenerator $invoiceNumberGenerator,
        protected StockMovementService $stockMovementService,
        protected OutstandingLedgerService $outstandingLedgerService,
        protected PaymentLinkService $paymentLinkService,
        protected AuditLogService $auditLogService,
        protected UomConversionService $uomConversionService,
        protected DueDateService $dueDateService,
        protected SerialBatchService $serialBatchService,
    ) {}

    public function convertToInvoice(
        Order $order,
        ?string $actorName = null
    ): Invoice {
        $actor = auth()->user();

        $actorName = $this->auditLogService->actorName(
            $actorName
        );

        return DB::transaction(function () use (
            $order,
            $actor,
            $actorName
        ) {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Idempotency:
             *
             * If conversion already happened, return the existing
             * invoice instead of creating another one.
             */
            $existingInvoice = Invoice::query()
                ->where('order_id', $lockedOrder->id)
                ->first();

            if ($existingInvoice) {
                if ($lockedOrder->status !== 'converted') {
                    $lockedOrder->update([
                        'status' => 'converted',
                        'converted_by_name' => $actorName,
                    ]);
                }

                return $existingInvoice->load('items');
            }

            /*
             * Approval is mandatory before invoice conversion.
             */
            if ($lockedOrder->status !== 'approved') {
                throw new InvalidArgumentException(
                    "Order status [{$lockedOrder->status}] cannot be converted. "
                    . 'Only approved orders can be converted to an invoice.'
                );
            }

            $lockedOrder->load([
                'items.product.baseUom',
                'items.uom',
                'customer',
                'salesperson',
            ]);

            if ($lockedOrder->items->isEmpty()) {
                throw new InvalidArgumentException(
                    'Cannot convert an order without line items.'
                );
            }

            $customer = $lockedOrder->customer;
            $invoiceDate = now()->toDateString();
            $due = $this->dueDateService->forSalesInvoice($customer, $invoiceDate);

            $deliveryState = $lockedOrder->shippingAddress?->state ?? ($lockedOrder->billingAddress?->state ?? ($customer->shipping_state ?? ($customer->state ?? '')));
            $company = $lockedOrder->warehouse?->company ?? $customer->company ?? \App\Domains\Organization\Models\Company::resolveCurrent() ?? \App\Domains\Organization\Models\Company::first();
            $sellerState = \App\Domains\Master\Services\GstTaxCalculationService::resolveSellerState($company);
            $isIntraState = \App\Domains\Master\Services\GstTaxCalculationService::isIntraState($sellerState, $deliveryState);

            $orderTax = (float) $lockedOrder->tax_amount;
            $cgstTotal = $isIntraState ? round($orderTax / 2, 2) : 0.0;
            $sgstTotal = $isIntraState ? round($orderTax - $cgstTotal, 2) : 0.0;
            $igstTotal = $isIntraState ? 0.0 : $orderTax;

            $roundOff = (float) ($lockedOrder->round_off ?? 0);
            $grandTotal = (float) $lockedOrder->grand_total;

            $buyerGstin = trim((string) ($lockedOrder->billingAddress?->gstin ?: ($customer->gstin ?: '')));
            $isB2B = $buyerGstin !== '' && strtoupper($buyerGstin) !== 'URP' && strlen($buyerGstin) === 15;
            $einvoiceStatus = $isB2B ? 'pending' : 'not_applicable';

            $isOverThreshold = $grandTotal >= 50000.00;
            $ewayStatus = $isOverThreshold ? 'pending' : 'not_applicable';

            $invoice = Invoice::create([
                'company_id' => $lockedOrder->warehouse?->company_id ?? $customer->company_id ?? \App\Domains\Organization\Models\Company::resolveCurrent()?->id,
                'invoice_no' => $this->invoiceNumberGenerator->generate(),
                'customer_id' => $lockedOrder->customer_id,
                'order_id' => $lockedOrder->id,
                'salesperson_id' => $lockedOrder->salesperson_id,
                'invoice_date' => $invoiceDate,
                'due_date' => $due['due_date'],
                'due_date_basis' => $due['due_date_basis'],
                'due_date_source_date' => $due['due_date_source_date'],
                'payment_terms' => $customer->payment_terms,
                'credit_days' => $due['credit_days'],
                'price_type' => $lockedOrder->price_type ?? 'exclusive',
                'tax_type' => $isIntraState ? 'intra' : 'inter',
                'status' => 'issued',
                'einvoice_status' => $einvoiceStatus,
                'eway_bill_status' => $ewayStatus,
                'eway_bill_required' => 'auto',
                'subtotal' => $lockedOrder->subtotal,
                'taxable_amount' => $lockedOrder->subtotal,
                'discount_amount' => $lockedOrder->discount_amount,
                'tax_amount' => $lockedOrder->tax_amount,
                'cgst_amount' => $cgstTotal,
                'sgst_amount' => $sgstTotal,
                'igst_amount' => $igstTotal,
                'round_off' => $roundOff,
                'grand_total' => $grandTotal,
                'delivery_state' => $deliveryState,
                'place_of_supply_state' => $deliveryState,
                'billing_address_id' => $lockedOrder->billing_address_id,
                'shipping_address_id' => $lockedOrder->shipping_address_id,
                'billing_address' => $lockedOrder->billing_address,
                'shipping_address' => $lockedOrder->shipping_address,
                'paid_amount' => 0,
                'notes' => $lockedOrder->notes,
            ]);

            $items = $lockedOrder->items->values();

            $subtotal = (float) $lockedOrder->subtotal;
            $orderDiscount = (float) $lockedOrder->discount_amount;
            $orderTax = (float) $lockedOrder->tax_amount;

            $runningDiscount = 0.0;
            $runningTax = 0.0;

            foreach ($items as $index => $item) {
                $lineSubtotal = (float) $item->line_total;

                $isLastItem = $index === $items->count() - 1;

                if ($subtotal > 0) {
                    if ($isLastItem) {
                        $lineDiscount = round(
                            $orderDiscount - $runningDiscount,
                            2
                        );

                        $lineTax = round(
                            $orderTax - $runningTax,
                            2
                        );
                    } else {
                        $ratio = $lineSubtotal / $subtotal;

                        $lineDiscount = round(
                            $orderDiscount * $ratio,
                            2
                        );

                        $lineTax = round(
                            $orderTax * $ratio,
                            2
                        );

                        $runningDiscount += $lineDiscount;
                        $runningTax += $lineTax;
                    }
                } else {
                    $lineDiscount = 0.0;
                    $lineTax = 0.0;
                }

                /*
                 * IMPORTANT:
                 *
                 * Invoice keeps the customer's selected UOM and quantity.
                 */
                $serials = WarrantyPeriod::split($item->serial_numbers);

                $lineTaxRate = (float) ($item->product?->tax_rate ?? 0);
                $lineCgst = $isIntraState ? round($lineTax / 2, 2) : 0.0;
                $lineSgst = $isIntraState ? round($lineTax - $lineCgst, 2) : 0.0;
                $lineIgst = $isIntraState ? 0.0 : $lineTax;

                $invoiceItem = InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item->product_id,
                    'uom_id' => $item->uom_id,
                    'quantity' => $item->quantity,
                    'price_type' => $lockedOrder->price_type ?? 'exclusive',
                    'entered_unit_price' => $item->unit_price,
                    'unit_price' => $item->unit_price,
                    'discount_amount' => $lineDiscount,
                    'taxable_amount' => max(0, $lineSubtotal - $lineDiscount),
                    'tax_rate' => $lineTaxRate,
                    'cgst_rate' => $isIntraState ? round($lineTaxRate / 2, 2) : 0,
                    'sgst_rate' => $isIntraState ? round($lineTaxRate / 2, 2) : 0,
                    'igst_rate' => $isIntraState ? 0 : $lineTaxRate,
                    'tax_amount' => $lineTax,
                    'cgst_amount' => $lineCgst,
                    'sgst_amount' => $lineSgst,
                    'igst_amount' => $lineIgst,
                    'line_total' => $item->line_total,
                    'serial_numbers' => $serials !== [] ? $serials : null,
                ]);

                if ($serials !== [] && $item->product) {
                    $this->serialBatchService->sellSerials($serials, $invoice, $item->product, $invoiceItem);
                }

                /*
                 * IMPORTANT:
                 *
                 * Inventory is always deducted in the product's
                 * base UOM.
                 *
                 * Example:
                 *
                 * 1 Case
                 * Case conversion factor = 10
                 * Base UOM = Box
                 *
                 * Stock deduction = 10 Box
                 */
                $baseUom = $item->product->baseUom;

                if (! $baseUom) {
                    throw new InvalidArgumentException(
                        "Product [{$item->product->name}] does not have a base UOM configured."
                    );
                }

                $baseQuantity = $this->uomConversionService
                    ->toBaseQuantity(
                        product: $item->product,
                        quantity: (float) $item->quantity,
                        fromUom: $item->uom,
                    );

                $baseQuantity = round($baseQuantity, 4);

                if ($baseQuantity <= 0) {
                    throw new InvalidArgumentException(
                        "Invalid stock quantity for {$item->product->name}."
                    );
                }

                $this->stockMovementService->recordOut(
                    product: $item->product,
                    uom: $baseUom,
                    quantity: $baseQuantity,
                    type: 'sale',
                    reference: $invoice,
                    notes: sprintf(
                        'Sale from order %s: %s %s = %s %s',
                        $lockedOrder->order_no,
                        rtrim(
                            rtrim(
                                number_format(
                                    (float) $item->quantity,
                                    4,
                                    '.',
                                    ''
                                ),
                                '0'
                            ),
                            '.'
                        ),
                        $item->uom->name,
                        rtrim(
                            rtrim(
                                number_format(
                                    $baseQuantity,
                                    4,
                                    '.',
                                    ''
                                ),
                                '0'
                            ),
                            '.'
                        ),
                        $baseUom->name
                    ),
                    user: $actor instanceof User
                        ? $actor
                        : null,
                );
            }

            /*
             * Create customer outstanding entry.
             */
            $this->outstandingLedgerService
                ->recordInvoice($invoice);

            /*
             * Create payment link only once.
             */
            $this->paymentLinkService
                ->createForInvoice($invoice);

            /*
             * Only mark the order converted after every downstream
             * operation has succeeded.
             */
            $lockedOrder->update([
                'status' => 'converted',
                'converted_by_name' => $actorName,
            ]);

            $this->auditLogService->record(
                $lockedOrder,
                'converted',
                $actorName
            );

            return $invoice->load('items');
        });
    }

    /**
     * @param array<int, int> $orderIds
     * @return Collection<int, Invoice>
     */
    public function convertMany(
        array $orderIds,
        ?string $actorName = null
    ): Collection {
        $orderIds = array_values(
            array_unique(
                array_map('intval', $orderIds)
            )
        );

        if ($orderIds === []) {
            throw new InvalidArgumentException(
                'No orders were selected for conversion.'
            );
        }

        $invoices = collect();

        foreach ($orderIds as $orderId) {
            $order = Order::query()
                ->whereKey($orderId)
                ->first();

            if (! $order) {
                throw new InvalidArgumentException(
                    "Order [{$orderId}] no longer exists."
                );
            }

            $invoices->push(
                $this->convertToInvoice(
                    $order,
                    $actorName
                )
            );
        }

        return $invoices;
    }
}