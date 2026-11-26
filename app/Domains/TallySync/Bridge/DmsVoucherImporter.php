<?php

namespace App\Domains\TallySync\Bridge;

use App\Domains\Inventory\Models\StockAdjustment;
use App\Domains\Inventory\Models\StockAdjustmentItem;
use App\Domains\Inventory\Models\StockTransfer;
use App\Domains\Inventory\Models\StockTransferItem;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderItem;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Payment\Models\Cheque;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\PaymentAllocation;
use App\Domains\Payment\Services\OutstandingLedgerService;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseInvoiceItem;
use App\Domains\Purchasing\Models\PurchaseInward;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Purchasing\Models\PurchaseOrderItem;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Domains\Sales\Models\Quotation;
use App\Domains\Sales\Models\QuotationItem;
use App\Domains\Tally\Services\TallySyncMappingService;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Schema;

/**
 * Tally VOUCHER collection → DMS commercial / inventory documents.
 * Skips rows that originated from DMS (REMOTEID dms-*).
 */
class DmsVoucherImporter
{
    /** @var array{created:int,updated:int,conflicts:int,skipped:int} */
    private array $counts;

    public function __construct(
        private Company $organization,
        array &$counts,
    ) {
        $this->counts = &$counts;
    }

    public function import(\DOMElement $node): void
    {
        if ($this->yes($this->text($node, 'ISDELETED')) || $this->yes($this->text($node, 'ISCANCELLED'))) {
            $this->counts['skipped']++;

            return;
        }

        $remote = trim($node->getAttribute('REMOTEID'));
        if ($remote !== '' && str_starts_with(mb_strtolower($remote), 'dms-')) {
            $this->counts['skipped']++;

            return;
        }

        $typeName = $this->text($node, 'VOUCHERTYPENAME') ?: $node->getAttribute('VCHTYPE');
        $normalized = mb_strtolower(trim($typeName));
        if ($normalized === '') {
            $this->counts['skipped']++;

            return;
        }

        match (true) {
            str_contains($normalized, 'stock journal') || str_contains($normalized, 'physical stock') => $this->stockJournal($node),
            str_contains($normalized, 'sales order') => $this->salesOrder($node),
            str_contains($normalized, 'purchase order') => $this->purchaseOrder($node),
            str_contains($normalized, 'receipt note') || str_contains($normalized, 'goods receipt') || str_contains($normalized, 'material in') => $this->goodsReceipt($node),
            str_contains($normalized, 'credit note') || str_contains($normalized, 'sale return') || str_contains($normalized, 'sales return') => $this->creditNote($node),
            str_contains($normalized, 'quotation') || str_contains($normalized, 'proforma') => $this->quotation($node),
            str_contains($normalized, 'delivery chall') || ($normalized === 'delivery' || str_contains($normalized, 'delivery challan')) => $this->deliveryChallan($node),
            str_contains($normalized, 'gst tax invoice') || str_contains($normalized, 'tax invoice') || $normalized === 'sales' || str_contains($normalized, 'sales') => $this->salesInvoice($node, $typeName),
            $normalized === 'purchase' || str_contains($normalized, 'purchase') => $this->purchaseInvoice($node),
            $normalized === 'receipt' || str_contains($normalized, 'receipt') => $this->receipt($node),
            $normalized === 'payment' || str_contains($normalized, 'payment') => $this->paymentVoucher($node),
            // Journal / Contra / Debit Note → Books (acct_vouchers); combined import shows them in DMS Day Book.
            in_array($normalized, ['journal', 'contra'], true)
                || str_contains($normalized, 'journal')
                || str_contains($normalized, 'contra')
                || str_contains($normalized, 'debit note') => $this->deferredToBooks($node, $typeName),
            default => $this->unsupported($node, $typeName, 'Unsupported DMS voucher type.'),
        };
    }

    /**
     * Quiet no-op on the DMS commercial path — Books stage imports these and
     * FinancialReportController Day Book already merges acct_vouchers.
     * Do not inflate skipped/conflicts; this is intentional routing.
     */
    private function deferredToBooks(\DOMElement $node, string $_typeName): void
    {
        // Intentionally silent.
    }

    private function unsupported(\DOMElement $node, string $typeName, string $reason): void
    {
        // Delivery challan etc. have no DMS twin — quiet (Books may still import accounting).
        // Do not inflate conflict counters during bulk Tally XML import.
    }

    private function salesInvoice(\DOMElement $node, string $typeName = 'Sales'): void
    {
        $number = $this->voucherNumber($node);
        $date = $this->date($this->text($node, 'DATE'));
        $partyName = $this->text($node, 'PARTYLEDGERNAME') ?: $this->text($node, 'PARTYNAME');
        $party = $this->ensureParty($partyName !== '' ? $partyName : 'Cash', 'customer');
        if (! $number || ! $date || ! $party) {
            $this->counts['skipped']++;

            return;
        }

        // Plain "Sales" vouchers often reuse short numbers (2, 4, 51…) — keep them distinct from GST tax invoices.
        $normalizedType = mb_strtolower(trim($typeName));
        if ($normalizedType === 'sales' && ! str_contains(mb_strtolower($number), 'avit')) {
            $number = 'SL-'.$number.'-'.str_replace('-', '', $date);
        }

        $this->ensureFinancialYear($date);
        $gst = $this->voucherGst($node);
        $lines = $this->inventoryLines($node, $gst);
        $totals = $this->sumLines($lines, $gst);

        $invoice = Invoice::query()
            ->where('company_id', $this->organization->id)
            ->where('invoice_no', $number)
            ->first();

        $attrs = [
            'company_id' => $this->organization->id,
            'invoice_no' => $number,
            'customer_id' => $party->id,
            'salesperson_id' => $this->resolveSalespersonId($node),
            'invoice_date' => $date,
            'status' => 'issued',
            'price_type' => 'exclusive',
            'tax_type' => $totals['tax_type'],
            'subtotal' => $totals['subtotal'],
            'taxable_amount' => $totals['subtotal'],
            'tax_amount' => $totals['tax'],
            'cgst_amount' => $totals['cgst'],
            'sgst_amount' => $totals['sgst'],
            'igst_amount' => $totals['igst'],
            'grand_total' => $totals['grand'],
            'notes' => $this->text($node, 'NARRATION') ?: null,
            'reference_no' => $this->text($node, 'REFERENCE') ?: null,
        ];

        if ($invoice) {
            $invoice->fill($attrs)->save();
            $invoice->items()->delete();
            $this->counts['updated']++;
        } else {
            try {
                $invoice = Invoice::query()->create($attrs);
                $this->counts['created']++;
            } catch (UniqueConstraintViolationException) {
                $invoice = Invoice::query()
                    ->where('company_id', $this->organization->id)
                    ->where('invoice_no', $number)
                    ->firstOrFail();
                $invoice->fill($attrs)->save();
                $invoice->items()->delete();
                $this->counts['updated']++;
            }
        }

        $this->writeInvoiceItems($invoice->id, $lines);
        $this->map('voucher', Invoice::class, $invoice->id, $node, $number);
    }

    private function purchaseInvoice(\DOMElement $node): void
    {
        $number = $this->voucherNumber($node);
        $date = $this->date($this->text($node, 'DATE'));
        $party = $this->ensureParty($this->text($node, 'PARTYLEDGERNAME') ?: $this->text($node, 'PARTYNAME'), 'supplier');
        if (! $number || ! $date || ! $party) {
            $this->counts['skipped']++;

            return;
        }

        $this->ensureFinancialYear($date);
        $gst = $this->voucherGst($node);
        $lines = $this->inventoryLines($node, $gst);
        $totals = $this->sumLines($lines, $gst);
        $warehouse = $this->firstWarehouseFromLines($lines);

        $doc = PurchaseInvoice::query()
            ->where('company_id', $this->organization->id)
            ->where('invoice_no', $number)
            ->first();

        $attrs = [
            'company_id' => $this->organization->id,
            'invoice_no' => $number,
            'supplier_id' => $party->id,
            'warehouse_id' => $warehouse?->id,
            'invoice_date' => $date,
            'status' => 'posted',
            'price_type' => 'exclusive',
            'tax_type' => $totals['tax_type'],
            'subtotal' => $totals['subtotal'],
            'taxable_amount' => $totals['subtotal'],
            'tax_amount' => $totals['tax'],
            'cgst_amount' => $totals['cgst'],
            'sgst_amount' => $totals['sgst'],
            'igst_amount' => $totals['igst'],
            'grand_total' => $totals['grand'],
            'notes' => $this->text($node, 'NARRATION') ?: null,
        ];

        if ($doc) {
            $doc->fill($attrs)->save();
            $doc->items()->delete();
            $this->counts['updated']++;
        } else {
            $doc = PurchaseInvoice::query()->create($attrs);
            $this->counts['created']++;
        }

        foreach ($lines as $line) {
            PurchaseInvoiceItem::query()->create([
                'purchase_invoice_id' => $doc->id,
                'product_id' => $line['product_id'],
                'uom_id' => $line['uom_id'],
                'quantity' => $line['qty'],
                'unit_cost' => $line['rate'],
                'entered_unit_cost' => $line['rate'],
                'price_type' => 'exclusive',
                'taxable_amount' => $line['amount'],
                'tax_percent' => $line['tax_rate'],
                'cgst_percent' => $line['cgst_rate'],
                'sgst_percent' => $line['sgst_rate'],
                'igst_percent' => $line['igst_rate'],
                'cgst_amount' => $line['cgst'],
                'sgst_amount' => $line['sgst'],
                'igst_amount' => $line['igst'],
                'line_total' => round($line['amount'] + $line['tax'], 2),
            ]);
        }

        $this->map('voucher', PurchaseInvoice::class, $doc->id, $node, $number);
    }

    private function receipt(\DOMElement $node): void
    {
        $this->moneyVoucher($node, 'receipt');
    }

    private function paymentVoucher(\DOMElement $node): void
    {
        $this->moneyVoucher($node, 'payment');
    }

    private function moneyVoucher(\DOMElement $node, string $kind): void
    {
        $number = $this->voucherNumber($node);
        $date = $this->date($this->text($node, 'DATE'));
        $partyName = $this->text($node, 'PARTYLEDGERNAME') ?: $this->text($node, 'PARTYNAME');
        $party = $this->ensureParty($partyName !== '' ? $partyName : 'Cash', $kind === 'receipt' ? 'customer' : 'supplier');
        $amount = abs($this->partyAmount($node));
        if (! $number || ! $date || ! $party || $amount < 0.01) {
            $this->counts['skipped']++;

            return;
        }

        // Keep Receipt vs Payment series distinct when Tally reuses short numbers.
        $paymentNo = $kind === 'payment' ? 'PAY-'.$number : 'RCT-'.$number;

        $this->ensureFinancialYear($date);
        $bank = $this->firstBankAllocation($node);
        $method = $this->paymentMethodFromBank($bank);

        $payment = Payment::query()
            ->where('payment_no', $paymentNo)
            ->orWhere('payment_no', $number)
            ->first();

        $attrs = [
            'payment_no' => $paymentNo,
            'reference_no' => $number,
            'customer_id' => $party->id,
            'amount' => $amount,
            'status' => 'completed',
            'method' => $method,
            'paid_at' => $date,
            'notes' => $this->text($node, 'NARRATION') ?: null,
        ];

        if ($payment) {
            $payment->fill($attrs)->save();
            $this->counts['updated']++;
        } else {
            try {
                $payment = Payment::query()->create($attrs);
                $this->counts['created']++;
            } catch (UniqueConstraintViolationException) {
                $payment = Payment::query()->where('payment_no', $paymentNo)->firstOrFail();
                $payment->fill($attrs)->save();
                $this->counts['updated']++;
            }
        }

        if ($kind === 'receipt') {
            $this->allocatePaymentBills($payment, $node);
            try {
                app(OutstandingLedgerService::class)->recordPayment($payment);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        $this->syncChequeFromBankAllocation($payment, $party, $node, $bank, $kind, $date, $amount);
        $this->map('voucher', Payment::class, (int) $payment->id, $node, $paymentNo);
    }

    private function creditNote(\DOMElement $node): void
    {
        $number = $this->voucherNumber($node);
        $date = $this->date($this->text($node, 'DATE'));
        $party = $this->ensureParty($this->text($node, 'PARTYLEDGERNAME') ?: $this->text($node, 'PARTYNAME') ?: 'Cash', 'customer');
        $amount = abs($this->partyAmount($node));
        if (! $number || ! $date || ! $party) {
            $this->counts['skipped']++;

            return;
        }

        $this->ensureFinancialYear($date);

        $doc = CreditNote::query()->where('credit_note_no', $number)->first();

        $attrs = [
            'credit_note_no' => $number,
            'customer_id' => $party->id,
            'credit_note_date' => $date,
            'subtotal' => $amount > 0 ? $amount : 0,
            'tax_amount' => 0,
            'grand_total' => $amount > 0 ? $amount : 0,
            'status' => 'posted',
            'notes' => $this->text($node, 'NARRATION') ?: null,
        ];

        if ($doc) {
            $doc->fill($attrs)->save();
            $this->counts['updated']++;
        } else {
            try {
                $doc = CreditNote::query()->create($attrs);
                $this->counts['created']++;
            } catch (UniqueConstraintViolationException) {
                $doc = CreditNote::query()->where('credit_note_no', $number)->firstOrFail();
                $doc->fill($attrs)->save();
                $this->counts['updated']++;
            }
        }

        try {
            app(OutstandingLedgerService::class)->recordCreditNote($doc);
        } catch (\Throwable $exception) {
            report($exception);
        }

        $this->map('voucher', CreditNote::class, (int) $doc->id, $node, $number);
    }

    /**
     * @return array{instrument:?string,instrument_date:?string,bank_name:?string,txn_type:?string,payment_mode:?string,amount:float}|null
     */
    private function firstBankAllocation(\DOMElement $node): ?array
    {
        foreach ($node->getElementsByTagName('BANKALLOCATIONS.LIST') as $alloc) {
            if (! $alloc instanceof \DOMElement) {
                continue;
            }
            $instrument = trim($this->text($alloc, 'INSTRUMENTNUMBER') ?: $this->text($alloc, 'CHEQUENUMBER') ?: $this->text($alloc, 'CHEQUENO'));
            $bankName = trim($this->text($alloc, 'BANKNAME'));
            $txnType = trim($this->text($alloc, 'TRANSACTIONTYPE'));
            $mode = trim($this->text($alloc, 'PAYMENTMODE') ?: $this->text($alloc, 'PAYMENTMODE'));
            $instDate = $this->date($this->text($alloc, 'INSTRUMENTDATE') ?: $this->text($alloc, 'CHEQUEDATE'));
            $amount = abs($this->leadingNumber($this->text($alloc, 'AMOUNT')));
            if ($instrument === '' && $bankName === '' && $txnType === '' && $amount < 0.01) {
                continue;
            }

            return [
                'instrument' => $instrument !== '' ? $instrument : null,
                'instrument_date' => $instDate,
                'bank_name' => $bankName !== '' ? $bankName : null,
                'txn_type' => $txnType !== '' ? $txnType : null,
                'payment_mode' => $mode !== '' ? $mode : null,
                'amount' => $amount,
            ];
        }

        return null;
    }

    /**
     * @param  array{instrument:?string,instrument_date:?string,bank_name:?string,txn_type:?string,payment_mode:?string,amount:float}|null  $bank
     */
    private function paymentMethodFromBank(?array $bank): string
    {
        if (! $bank) {
            return 'bank';
        }
        $blob = mb_strtolower(trim(($bank['txn_type'] ?? '').' '.($bank['payment_mode'] ?? '')));
        if (str_contains($blob, 'cash')) {
            return 'cash';
        }
        if (str_contains($blob, 'cheque') || str_contains($blob, 'check')) {
            return 'cheque';
        }
        if (str_contains($blob, 'upi') || str_contains($blob, 'imps') || str_contains($blob, 'neft') || str_contains($blob, 'rtgs')) {
            return 'upi';
        }
        // Numeric/short instrument with no electronic mode usually means cheque leaf.
        $instrument = trim((string) ($bank['instrument'] ?? ''));
        if ($instrument !== '' && preg_match('/^\d{4,10}$/', $instrument) === 1) {
            return 'cheque';
        }

        return 'bank';
    }

    private function allocatePaymentBills(Payment $payment, \DOMElement $node): void
    {
        if (! Schema::hasTable('payment_allocations')) {
            return;
        }

        PaymentAllocation::query()->where('payment_id', $payment->id)->delete();

        foreach ($node->getElementsByTagName('BILLALLOCATIONS.LIST') as $bill) {
            if (! $bill instanceof \DOMElement) {
                continue;
            }
            $billName = trim($this->text($bill, 'NAME') ?: $this->text($bill, 'BILLNAME'));
            $billAmount = abs($this->leadingNumber($this->text($bill, 'AMOUNT')));
            if ($billName === '' || $billAmount < 0.01) {
                continue;
            }
            $invoice = Invoice::query()
                ->where('company_id', $this->organization->id)
                ->where(function ($q) use ($billName) {
                    $q->where('invoice_no', $billName)
                        ->orWhere('invoice_no', 'like', $billName.'%')
                        ->orWhere('reference_no', $billName);
                })
                ->first();
            if (! $invoice) {
                continue;
            }

            PaymentAllocation::query()->create([
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount' => $billAmount,
            ]);

            if (Schema::hasColumn('invoices', 'paid_amount')) {
                $newPaid = min((float) $invoice->grand_total, (float) $invoice->paid_amount + $billAmount);
                $invoice->forceFill(['paid_amount' => $newPaid])->save();
            }
            if (! $payment->invoice_id) {
                $payment->forceFill(['invoice_id' => $invoice->id])->save();
            }
        }
    }

    /**
     * @param  array{instrument:?string,instrument_date:?string,bank_name:?string,txn_type:?string,payment_mode:?string,amount:float}|null  $bank
     */
    private function syncChequeFromBankAllocation(
        Payment $payment,
        Customer $party,
        \DOMElement $node,
        ?array $bank,
        string $kind,
        string $date,
        float $amount,
    ): void {
        if (! $bank || blank($bank['instrument'] ?? null) || ! Schema::hasTable('cheques')) {
            return;
        }

        // Only persist cheque register rows for cheque instruments (not UTR/NEFT refs).
        $method = $this->paymentMethodFromBank($bank);
        if ($method !== 'cheque') {
            return;
        }

        $chequeNo = trim((string) $bank['instrument']);
        $isIssued = $kind !== 'receipt';
        $chequeType = $isIssued ? Cheque::TYPE_ISSUED : Cheque::TYPE_RECEIVED;
        $direction = $isIssued ? 'issued_to_vendor' : 'received_from_client';
        $status = $isIssued ? Cheque::STATUS_ISSUED : Cheque::STATUS_RECEIVED;
        $chequeDate = $bank['instrument_date'] ?: $date;
        $companyBank = trim((string) ($this->organization->bank_account_no ?? ''));

        $existing = Cheque::query()
            ->where('company_id', $this->organization->id)
            ->where('cheque_no', $chequeNo)
            ->where('customer_id', $party->id)
            ->first();

        $attrs = [
            'company_id' => $this->organization->id,
            'bank_account_no' => $companyBank !== '' ? $companyBank : null,
            'cheque_no' => $chequeNo,
            'customer_id' => $party->id,
            'purpose' => 'pdc',
            'direction' => $direction,
            'cheque_type' => $chequeType,
            'amount' => ($bank['amount'] ?? 0) > 0 ? $bank['amount'] : $amount,
            'bank_name' => $bank['bank_name'],
            'cheque_date' => $chequeDate,
            'status' => $status,
            'payment_id' => $payment->id,
            'reference_voucher' => $payment->payment_no,
            'voucher_type' => $kind === 'receipt' ? 'receipt' : 'payment',
            'notes' => $this->text($node, 'NARRATION') ?: null,
            'recorded_by' => $this->defaultSalespersonId(),
        ];

        try {
            if ($existing) {
                $existing->fill($attrs)->save();
            } else {
                Cheque::query()->create($attrs);
                $this->counts['created']++;
            }
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function salesOrder(\DOMElement $node): void
    {
        $number = $this->voucherNumber($node);
        $date = $this->date($this->text($node, 'DATE'));
        $party = $this->ensureParty($this->text($node, 'PARTYLEDGERNAME') ?: $this->text($node, 'PARTYNAME'), 'customer');
        if (! $number || ! $date || ! $party) {
            $this->counts['skipped']++;

            return;
        }

        $gst = $this->voucherGst($node);
        $lines = $this->inventoryLines($node, $gst);
        $totals = $this->sumLines($lines, $gst);

        $order = Order::query()->where('order_no', $number)->first();

        $attrs = [
            'order_no' => $number,
            'customer_id' => $party->id,
            'salesperson_id' => $this->resolveSalespersonId($node) ?: $this->defaultSalespersonId(),
            'order_date' => $date,
            'status' => 'approved',
            'subtotal' => $totals['subtotal'],
            'tax_amount' => $totals['tax'],
            'grand_total' => $totals['grand'],
            'notes' => $this->text($node, 'NARRATION') ?: null,
        ];

        if ($order) {
            $order->fill($attrs)->save();
            $order->items()->delete();
            $this->counts['updated']++;
        } else {
            try {
                $order = Order::query()->create($attrs);
                $this->counts['created']++;
            } catch (UniqueConstraintViolationException) {
                $order = Order::query()->where('order_no', $number)->firstOrFail();
                $order->fill($attrs)->save();
                $order->items()->delete();
                $this->counts['updated']++;
            }
        }

        foreach ($lines as $line) {
            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => $line['product_id'],
                'uom_id' => $line['uom_id'],
                'quantity' => $line['qty'],
                'unit_price' => $line['rate'],
                'line_total' => $line['amount'],
            ]);
        }

        $this->map('voucher', Order::class, $order->id, $node, $number);
    }

    private function purchaseOrder(\DOMElement $node): void
    {
        $number = $this->voucherNumber($node);
        $date = $this->date($this->text($node, 'DATE'));
        $party = $this->ensureParty($this->text($node, 'PARTYLEDGERNAME') ?: $this->text($node, 'PARTYNAME'), 'supplier');
        if (! $number || ! $date || ! $party) {
            $this->counts['skipped']++;

            return;
        }

        $gst = $this->voucherGst($node);
        $lines = $this->inventoryLines($node, $gst);
        $totals = $this->sumLines($lines, $gst);

        $doc = PurchaseOrder::query()
            ->where('company_id', $this->organization->id)
            ->where('po_no', $number)
            ->first();

        $attrs = [
            'company_id' => $this->organization->id,
            'po_no' => $number,
            'supplier_id' => $party->id,
            'po_date' => $date,
            'status' => 'approved',
            'subtotal' => $totals['subtotal'],
            'tax_amount' => $totals['tax'],
            'grand_total' => $totals['grand'],
            'notes' => $this->text($node, 'NARRATION') ?: null,
        ];

        if ($doc) {
            $doc->fill($attrs)->save();
            $doc->items()->delete();
            $this->counts['updated']++;
        } else {
            $doc = PurchaseOrder::query()->create($attrs);
            $this->counts['created']++;
        }

        foreach ($lines as $line) {
            PurchaseOrderItem::query()->create([
                'purchase_order_id' => $doc->id,
                'product_id' => $line['product_id'],
                'uom_id' => $line['uom_id'],
                'quantity' => $line['qty'],
                'unit_cost' => $line['rate'],
                'tax_percent' => $line['tax_rate'],
                'cgst_percent' => $line['cgst_rate'],
                'sgst_percent' => $line['sgst_rate'],
                'cgst_amount' => $line['cgst'],
                'sgst_amount' => $line['sgst'],
                'line_total' => round($line['amount'] + $line['tax'], 2),
            ]);
        }

        $this->map('voucher', PurchaseOrder::class, $doc->id, $node, $number);
    }

    private function quotation(\DOMElement $node): void
    {
        $number = $this->voucherNumber($node);
        $date = $this->date($this->text($node, 'DATE'));
        $party = $this->ensureParty($this->text($node, 'PARTYLEDGERNAME') ?: $this->text($node, 'PARTYNAME'), 'customer');
        if (! $number || ! $date || ! $party) {
            $this->counts['skipped']++;

            return;
        }

        // Tally often reuses the same PROFORMA number (e.g. PI-382) across parties/dates.
        $uniqueNo = $number;
        $clash = Quotation::query()
            ->where('quotation_no', $number)
            ->whereDate('quotation_date', '!=', $date)
            ->exists();
        if ($clash || Quotation::query()->where('quotation_no', $number)->where('customer_id', '!=', $party->id)->exists()) {
            $uniqueNo = $number.'/'.$date;
        }

        $gst = $this->voucherGst($node);
        $lines = $this->inventoryLines($node, $gst);
        $totals = $this->sumLines($lines, $gst);

        $doc = Quotation::query()
            ->where('quotation_no', $uniqueNo)
            ->orWhere(function ($q) use ($number, $date, $party) {
                $q->where('quotation_no', $number)
                    ->whereDate('quotation_date', $date)
                    ->where('customer_id', $party->id);
            })
            ->first();

        $attrs = [
            'company_id' => $this->organization->id,
            'quotation_no' => $uniqueNo,
            'customer_id' => $party->id,
            'salesperson_id' => $this->resolveSalespersonId($node) ?: $this->defaultSalespersonId(),
            'quotation_date' => $date,
            'status' => 'sent',
            'subtotal' => $totals['subtotal'],
            'tax_amount' => $totals['tax'],
            'grand_total' => $totals['grand'],
            'notes' => $this->text($node, 'NARRATION') ?: null,
        ];

        if ($doc) {
            $doc->fill($attrs)->save();
            $doc->items()->delete();
            $this->counts['updated']++;
        } else {
            try {
                $doc = Quotation::query()->create($attrs);
                $this->counts['created']++;
            } catch (UniqueConstraintViolationException) {
                $doc = Quotation::query()->where('quotation_no', $uniqueNo)->firstOrFail();
                $doc->fill($attrs)->save();
                $doc->items()->delete();
                $this->counts['updated']++;
            }
        }

        foreach ($lines as $line) {
            QuotationItem::query()->create([
                'quotation_id' => $doc->id,
                'product_id' => $line['product_id'],
                'uom_id' => $line['uom_id'],
                'quantity' => $line['qty'],
                'unit_price' => $line['rate'],
                'line_total' => $line['amount'],
            ]);
        }

        $this->map('voucher', Quotation::class, $doc->id, $node, $uniqueNo);
    }

    /**
     * Delivery challan has no native trip/load twin in Tally logistics.
     * Import as a sales invoice tagged DC so stock/party movement is not lost.
     */
    private function deliveryChallan(\DOMElement $node): void
    {
        $rawNumber = $this->voucherNumber($node);
        $date = $this->date($this->text($node, 'DATE'));
        $partyName = $this->text($node, 'PARTYLEDGERNAME') ?: $this->text($node, 'PARTYNAME');
        $party = $this->ensureParty($partyName !== '' ? $partyName : 'Cash', 'customer');
        if (! $rawNumber || ! $date || ! $party) {
            $this->counts['skipped']++;

            return;
        }

        $safe = preg_replace('/[^A-Za-z0-9]+/', '-', trim($rawNumber)) ?: 'DC';
        $number = 'DC-'.trim($safe, '-').'-'.str_replace('-', '', $date);

        $this->ensureFinancialYear($date);
        $gst = $this->voucherGst($node);
        $lines = $this->inventoryLines($node, $gst);
        $totals = $this->sumLines($lines, $gst);

        $invoice = Invoice::query()
            ->where('company_id', $this->organization->id)
            ->where('invoice_no', $number)
            ->first();

        $attrs = [
            'company_id' => $this->organization->id,
            'invoice_no' => $number,
            'customer_id' => $party->id,
            'salesperson_id' => $this->resolveSalespersonId($node),
            'invoice_date' => $date,
            'status' => 'issued',
            'price_type' => 'exclusive',
            'tax_type' => $totals['tax_type'],
            'subtotal' => $totals['subtotal'],
            'taxable_amount' => $totals['subtotal'],
            'tax_amount' => $totals['tax'],
            'cgst_amount' => $totals['cgst'],
            'sgst_amount' => $totals['sgst'],
            'igst_amount' => $totals['igst'],
            'grand_total' => $totals['grand'],
            'notes' => trim(($this->text($node, 'NARRATION') ?: '').' [Delivery Challan '.$rawNumber.']'),
            'delivery_note_no' => $rawNumber,
            'reference_no' => $this->text($node, 'REFERENCE') ?: $rawNumber,
        ];

        if ($invoice) {
            $invoice->fill($attrs)->save();
            $invoice->items()->delete();
            $this->counts['updated']++;
        } else {
            try {
                $invoice = Invoice::query()->create($attrs);
                $this->counts['created']++;
            } catch (UniqueConstraintViolationException) {
                $invoice = Invoice::query()
                    ->where('company_id', $this->organization->id)
                    ->where('invoice_no', $number)
                    ->firstOrFail();
                $invoice->fill($attrs)->save();
                $invoice->items()->delete();
                $this->counts['updated']++;
            }
        }

        $this->writeInvoiceItems($invoice->id, $lines);
        $this->map('voucher', Invoice::class, $invoice->id, $node, $number);
    }

    private function goodsReceipt(\DOMElement $node): void
    {
        $number = $this->voucherNumber($node);
        $date = $this->date($this->text($node, 'DATE'));
        $party = $this->ensureParty($this->text($node, 'PARTYLEDGERNAME') ?: $this->text($node, 'PARTYNAME'), 'supplier');
        $lines = $this->inventoryLines($node);
        $warehouse = $this->firstWarehouseFromLines($lines);
        if (! $number || ! $date) {
            $this->counts['skipped']++;

            return;
        }

        $docQuery = PurchaseInward::query()->where('inward_no', $number);
        if (Schema::hasColumn('purchase_inwards', 'grn_no')) {
            $docQuery->orWhere('grn_no', $number);
        }
        $doc = $docQuery->first();

        $attrs = array_filter([
            'inward_no' => $number,
            'grn_no' => Schema::hasColumn('purchase_inwards', 'grn_no') ? $number : null,
            'supplier_id' => $party?->id,
            'warehouse_id' => $warehouse?->id,
            'inward_date' => $date,
            'status' => 'posted',
            'notes' => $this->text($node, 'NARRATION') ?: null,
        ], fn ($v) => $v !== null);

        if ($doc) {
            $doc->fill($attrs)->save();
            $this->counts['updated']++;
        } else {
            PurchaseInward::query()->create($attrs);
            $this->counts['created']++;
        }
    }

    private function stockJournal(\DOMElement $node): void
    {
        $number = $this->voucherNumber($node) ?: ('SJ-'.uniqid());
        $date = $this->date($this->text($node, 'DATE')) ?: now()->toDateString();
        $lines = $this->inventoryLines($node);
        if ($lines === []) {
            $this->counts['skipped']++;

            return;
        }

        $from = null;
        $to = null;
        foreach ($lines as $line) {
            $from = $from ?: $line['godown'];
            $to = $to ?: $line['destination'];
        }

        if ($from && $to && $from->id !== $to->id) {
            $transfer = StockTransfer::query()->where('transfer_no', $number)->first();
            $attrs = [
                'transfer_no' => $number,
                'transfer_date' => $date,
                'from_warehouse_id' => $from->id,
                'to_warehouse_id' => $to->id,
                'status' => 'received',
                'notes' => $this->text($node, 'NARRATION') ?: null,
            ];
            if ($transfer) {
                $transfer->fill($attrs)->save();
                $transfer->items()->delete();
                $this->counts['updated']++;
            } else {
                $transfer = StockTransfer::query()->create($attrs);
                $this->counts['created']++;
            }
            foreach ($lines as $line) {
                StockTransferItem::query()->create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $line['product_id'],
                    'uom_id' => $line['uom_id'],
                    'quantity' => abs($line['qty']),
                ]);
            }

            return;
        }

        $warehouse = $from ?: $to ?: Warehouse::query()->where('company_id', $this->organization->id)->orderBy('id')->first();
        if (! $warehouse) {
            $this->counts['skipped']++;

            return;
        }

        $adj = StockAdjustment::query()->where('adjustment_no', $number)->first();
        $attrs = [
            'adjustment_no' => $number,
            'adjustment_date' => $date,
            'warehouse_id' => $warehouse->id,
            'reason' => 'other',
            'status' => 'posted',
            'notes' => $this->text($node, 'NARRATION') ?: 'Imported from Tally',
        ];
        if ($adj) {
            $adj->fill($attrs)->save();
            $adj->items()->delete();
            $this->counts['updated']++;
        } else {
            $adj = StockAdjustment::query()->create($attrs);
            $this->counts['created']++;
        }
        foreach ($lines as $line) {
            StockAdjustmentItem::query()->create([
                'stock_adjustment_id' => $adj->id,
                'product_id' => $line['product_id'],
                'uom_id' => $line['uom_id'],
                'quantity' => $line['qty'],
            ]);
        }
    }

    /**
     * @param  array{cgst_rate:float,sgst_rate:float,igst_rate:float,tax_rate:float,cgst_amount:float,sgst_amount:float,igst_amount:float,tax_amount:float,tax_type:string}|null  $gst
     * @return list<array{product_id:int,uom_id:?int,qty:float,rate:float,amount:float,tax:float,tax_rate:float,cgst_rate:float,sgst_rate:float,igst_rate:float,cgst:float,sgst:float,igst:float,hsn_code:?string,godown:?Warehouse,destination:?Warehouse}>
     */
    private function inventoryLines(\DOMElement $node, ?array $gst = null): array
    {
        $gst ??= $this->emptyGst();
        $lines = [];
        $voucherDest = $this->text($node, 'DESTINATIONGODOWN') ?: $this->text($node, 'DESTINATIONGODOWNNAME');

        // Invoice/PO: ALLINVENTORYENTRIES. Consumption SJ: INVENTORYENTRIESIN/OUT (and sometimes INVENTORYENTRIES).
        $tagged = [
            'ALLINVENTORYENTRIES.LIST' => null,
            'INVENTORYENTRIES.LIST' => null,
            'INVENTORYENTRIESIN.LIST' => true,  // stock in
            'INVENTORYENTRIESOUT.LIST' => false, // stock out
        ];

        foreach ($tagged as $tag => $forceDeemedIn) {
            foreach ($node->getElementsByTagName($tag) as $entry) {
                if (! $entry instanceof \DOMElement || $entry->parentNode !== $node) {
                    continue;
                }
                $itemName = $this->text($entry, 'STOCKITEMNAME');
                if ($itemName === '') {
                    continue;
                }
                $product = $this->ensureProduct($itemName, $this->text($entry, 'BASEUNITS') ?: 'Nos');
                $batch = $entry->getElementsByTagName('BATCHALLOCATIONS.LIST')->item(0);
                $godownName = $this->text($entry, 'GODOWNNAME');
                if ($godownName === '' && $batch instanceof \DOMElement) {
                    $godownName = $this->text($batch, 'GODOWNNAME');
                }
                $destName = $this->text($entry, 'DESTINATIONGODOWNNAME');
                if ($destName === '' && $batch instanceof \DOMElement) {
                    $destName = $this->text($batch, 'DESTINATIONGODOWNNAME')
                        ?: $this->text($batch, 'DESTINATIONGODOWN')
                        ?: $this->text($batch, 'TARGETGODOWNNAME');
                }
                if ($destName === '' && $voucherDest !== '') {
                    $destName = $voucherDest;
                }
                $qtyText = $this->text($entry, 'BILLEDQTY') ?: $this->text($entry, 'ACTUALQTY');
                if ($qtyText === '' && $batch instanceof \DOMElement) {
                    $qtyText = $this->text($batch, 'BILLEDQTY') ?: $this->text($batch, 'ACTUALQTY');
                }
                $qty = abs($this->leadingNumber($qtyText));
                $deemedIn = $forceDeemedIn;
                if ($deemedIn === null) {
                    $flag = $this->text($entry, 'ISDEEMEDPOSITIVE');
                    $deemedIn = $flag === '' || strcasecmp($flag, 'Yes') === 0;
                }
                if (! $deemedIn) {
                    $qty = -abs($qty);
                }
                $amount = abs((float) str_replace(',', '', $this->text($entry, 'AMOUNT') ?: '0'));
                $rate = $this->leadingNumber($this->text($entry, 'RATE'));
                if ($rate <= 0 && abs($qty) > 0) {
                    $rate = round($amount / abs($qty), 2);
                }
                $taxable = $amount > 0 ? $amount : round(abs($qty) * $rate, 2);

                $lineGst = $this->lineGstFromEntry($entry, $product, $gst, $taxable, $rate);

                $lines[] = [
                    'product_id' => $product->id,
                    'uom_id' => $product->base_uom_id,
                    'qty' => $qty,
                    'rate' => $rate,
                    'amount' => $taxable,
                    'tax' => $lineGst['tax'],
                    'tax_rate' => $lineGst['tax_rate'],
                    'cgst_rate' => $lineGst['cgst_rate'],
                    'sgst_rate' => $lineGst['sgst_rate'],
                    'igst_rate' => $lineGst['igst_rate'],
                    'cgst' => $lineGst['cgst'],
                    'sgst' => $lineGst['sgst'],
                    'igst' => $lineGst['igst'],
                    'hsn_code' => $this->text($entry, 'GSTHSNNAME') ?: ($product->hsn_code ?? null),
                    'godown' => $godownName !== '' ? $this->ensureWarehouse($godownName) : null,
                    'destination' => $destName !== '' ? $this->ensureWarehouse($destName) : null,
                ];
            }
        }

        return $this->allocateVoucherTaxToLines($lines, $gst);
    }

    /**
     * @param  list<array{product_id:int,uom_id:?int,qty:float,rate:float,amount:float,tax:float,tax_rate:float,cgst_rate:float,sgst_rate:float,igst_rate:float,cgst:float,sgst:float,igst:float,hsn_code:?string}>  $lines
     */
    private function writeInvoiceItems(int $invoiceId, array $lines): void
    {
        foreach ($lines as $line) {
            InvoiceItem::query()->create([
                'invoice_id' => $invoiceId,
                'product_id' => $line['product_id'],
                'uom_id' => $line['uom_id'],
                'quantity' => abs($line['qty']),
                'price_type' => 'exclusive',
                'entered_unit_price' => $line['rate'],
                'unit_price' => $line['rate'],
                'taxable_amount' => $line['amount'],
                'tax_rate' => $line['tax_rate'],
                'cgst_rate' => $line['cgst_rate'],
                'sgst_rate' => $line['sgst_rate'],
                'igst_rate' => $line['igst_rate'],
                'hsn_code' => $line['hsn_code'] ?? null,
                'tax_amount' => $line['tax'],
                'cgst_amount' => $line['cgst'],
                'sgst_amount' => $line['sgst'],
                'igst_amount' => $line['igst'],
                'line_total' => round($line['amount'] + $line['tax'], 2),
            ]);
        }
    }

    /**
     * @param  list<array{amount:float,tax:float,cgst:float,sgst:float,igst:float}>  $lines
     * @param  array{cgst_amount:float,sgst_amount:float,igst_amount:float,tax_amount:float,tax_type:string}|null  $gst
     * @return array{subtotal:float,tax:float,cgst:float,sgst:float,igst:float,grand:float,tax_type:string}
     */
    private function sumLines(array $lines, ?array $gst = null): array
    {
        $subtotal = 0.0;
        $tax = 0.0;
        $cgst = 0.0;
        $sgst = 0.0;
        $igst = 0.0;
        foreach ($lines as $line) {
            $subtotal += (float) $line['amount'];
            $tax += (float) $line['tax'];
            $cgst += (float) ($line['cgst'] ?? 0);
            $sgst += (float) ($line['sgst'] ?? 0);
            $igst += (float) ($line['igst'] ?? 0);
        }

        // Prefer voucher duty ledger totals when present (exact Tally amounts).
        if ($gst && ($gst['tax_amount'] ?? 0) > 0.009) {
            $tax = (float) $gst['tax_amount'];
            $cgst = (float) $gst['cgst_amount'];
            $sgst = (float) $gst['sgst_amount'];
            $igst = (float) $gst['igst_amount'];
        }

        return [
            'subtotal' => round($subtotal, 2),
            'tax' => round($tax, 2),
            'cgst' => round($cgst, 2),
            'sgst' => round($sgst, 2),
            'igst' => round($igst, 2),
            'grand' => round($subtotal + $tax, 2),
            'tax_type' => $gst['tax_type'] ?? (($igst > 0.009 && ($cgst + $sgst) < 0.009) ? 'inter' : 'intra'),
        ];
    }

    /**
     * @param  list<array{godown:?Warehouse}>  $lines
     */
    private function firstWarehouseFromLines(array $lines): ?Warehouse
    {
        foreach ($lines as $line) {
            if ($line['godown'] ?? null) {
                return $line['godown'];
            }
        }

        return Warehouse::query()->where('company_id', $this->organization->id)->orderBy('id')->first();
    }

    /**
     * @return array{cgst_rate:float,sgst_rate:float,igst_rate:float,tax_rate:float,cgst_amount:float,sgst_amount:float,igst_amount:float,tax_amount:float,tax_type:string}
     */
    private function emptyGst(): array
    {
        return [
            'cgst_rate' => 0.0,
            'sgst_rate' => 0.0,
            'igst_rate' => 0.0,
            'tax_rate' => 0.0,
            'cgst_amount' => 0.0,
            'sgst_amount' => 0.0,
            'igst_amount' => 0.0,
            'tax_amount' => 0.0,
            'tax_type' => 'intra',
        ];
    }

    /**
     * Read voucher-level GST duty ledgers + RATEDETAILS from Tally XML.
     *
     * @return array{cgst_rate:float,sgst_rate:float,igst_rate:float,tax_rate:float,cgst_amount:float,sgst_amount:float,igst_amount:float,tax_amount:float,tax_type:string}
     */
    private function voucherGst(\DOMElement $node): array
    {
        $gst = $this->emptyGst();

        foreach ($node->getElementsByTagName('RATEDETAILS.LIST') as $rateNode) {
            if (! $rateNode instanceof \DOMElement) {
                continue;
            }
            $head = strtoupper($this->text($rateNode, 'GSTRATEDUTYHEAD'));
            $rate = $this->leadingNumber($this->text($rateNode, 'GSTRATE'));
            if ($rate <= 0) {
                continue;
            }
            if (str_contains($head, 'IGST')) {
                $gst['igst_rate'] = $rate;
            } elseif (str_contains($head, 'CGST')) {
                $gst['cgst_rate'] = $rate;
            } elseif (str_contains($head, 'SGST') || str_contains($head, 'UTGST')) {
                $gst['sgst_rate'] = $rate;
            }
        }

        foreach (['ALLLEDGERENTRIES.LIST', 'LEDGERENTRIES.LIST'] as $tag) {
            foreach ($node->getElementsByTagName($tag) as $entry) {
                if (! $entry instanceof \DOMElement) {
                    continue;
                }
                $duty = $this->gstDutyKind($this->text($entry, 'LEDGERNAME'));
                if ($duty === null) {
                    continue;
                }
                $amount = abs($this->leadingNumber($this->text($entry, 'AMOUNT')));
                if ($amount < 0.01) {
                    continue;
                }
                $gst[$duty.'_amount'] += $amount;
            }
        }

        $gst['tax_amount'] = round($gst['cgst_amount'] + $gst['sgst_amount'] + $gst['igst_amount'], 2);

        if ($gst['igst_amount'] > 0.009 && ($gst['cgst_amount'] + $gst['sgst_amount']) < 0.009) {
            $gst['tax_type'] = 'inter';
            $gst['tax_rate'] = $gst['igst_rate'] > 0
                ? $gst['igst_rate']
                : (($gst['cgst_rate'] + $gst['sgst_rate']) > 0 ? ($gst['cgst_rate'] + $gst['sgst_rate']) : 0.0);
        } else {
            $gst['tax_type'] = 'intra';
            $split = $gst['cgst_rate'] + $gst['sgst_rate'];
            $gst['tax_rate'] = $split > 0 ? $split : ($gst['igst_rate'] > 0 ? $gst['igst_rate'] : 0.0);
            if ($gst['cgst_rate'] <= 0 && $gst['sgst_rate'] <= 0 && $gst['tax_rate'] > 0) {
                $gst['cgst_rate'] = round($gst['tax_rate'] / 2, 2);
                $gst['sgst_rate'] = round($gst['tax_rate'] - $gst['cgst_rate'], 2);
            }
        }

        return $gst;
    }

    private function gstDutyKind(string $ledgerName): ?string
    {
        $name = strtoupper(trim($ledgerName));
        if ($name === '') {
            return null;
        }
        // Match duty ledgers like "CGST", "SGST", "Output CGST @ 9%" — not "SALE GST IN PUNJAB".
        if (preg_match('/\bIGST\b/', $name)) {
            return 'igst';
        }
        if (preg_match('/\b(?:SGST|UTGST)\b/', $name)) {
            return 'sgst';
        }
        if (preg_match('/\bCGST\b/', $name)) {
            return 'cgst';
        }

        return null;
    }

    /**
     * @param  array{cgst_rate:float,sgst_rate:float,igst_rate:float,tax_rate:float,tax_type:string}  $gst
     * @return array{tax:float,tax_rate:float,cgst_rate:float,sgst_rate:float,igst_rate:float,cgst:float,sgst:float,igst:float}
     */
    private function lineGstFromEntry(\DOMElement $entry, Product $product, array $gst, float $taxable, float $rate): array
    {
        $taxRate = (float) ($gst['tax_rate'] ?? 0);
        $cgstRate = (float) ($gst['cgst_rate'] ?? 0);
        $sgstRate = (float) ($gst['sgst_rate'] ?? 0);
        $igstRate = (float) ($gst['igst_rate'] ?? 0);

        // Derive from inclusive vs exclusive rate when masters omit RATEDETAILS.
        $incl = $this->leadingNumber($this->text($entry, 'INCLVATRATE'));
        if ($taxRate <= 0 && $incl > 0 && $rate > 0 && $incl > $rate) {
            $taxRate = round((($incl / $rate) - 1) * 100, 2);
        }

        if ($taxRate <= 0) {
            $taxRate = (float) ($product->tax_rate ?? 0);
        }

        if ($taxRate > 0 && $cgstRate <= 0 && $sgstRate <= 0 && $igstRate <= 0) {
            if (($gst['tax_type'] ?? 'intra') === 'inter') {
                $igstRate = $taxRate;
            } else {
                $cgstRate = round($taxRate / 2, 2);
                $sgstRate = round($taxRate - $cgstRate, 2);
            }
        }

        $tax = round($taxable * ($taxRate / 100), 2);
        if (($gst['tax_type'] ?? 'intra') === 'inter' || ($igstRate > 0 && ($cgstRate + $sgstRate) <= 0)) {
            return [
                'tax' => $tax,
                'tax_rate' => $taxRate,
                'cgst_rate' => 0.0,
                'sgst_rate' => 0.0,
                'igst_rate' => $igstRate > 0 ? $igstRate : $taxRate,
                'cgst' => 0.0,
                'sgst' => 0.0,
                'igst' => $tax,
            ];
        }

        $cgst = round($taxable * ($cgstRate / 100), 2);
        $sgst = round($tax - $cgst, 2);

        return [
            'tax' => $tax,
            'tax_rate' => $taxRate,
            'cgst_rate' => $cgstRate,
            'sgst_rate' => $sgstRate,
            'igst_rate' => 0.0,
            'cgst' => $cgst,
            'sgst' => $sgst,
            'igst' => 0.0,
        ];
    }

    /**
     * When line rates were 0 but voucher has duty totals, spread tax by taxable weight.
     *
     * @param  list<array<string,mixed>>  $lines
     * @param  array{tax_amount:float,cgst_amount:float,sgst_amount:float,igst_amount:float,tax_type:string,tax_rate:float,cgst_rate:float,sgst_rate:float,igst_rate:float}  $gst
     * @return list<array<string,mixed>>
     */
    private function allocateVoucherTaxToLines(array $lines, array $gst): array
    {
        if ($lines === [] || ($gst['tax_amount'] ?? 0) <= 0.009) {
            return $lines;
        }

        $lineTax = array_sum(array_map(fn ($l) => (float) $l['tax'], $lines));
        if ($lineTax > 0.009) {
            return $lines;
        }

        $taxableTotal = array_sum(array_map(fn ($l) => (float) $l['amount'], $lines));
        if ($taxableTotal <= 0) {
            return $lines;
        }

        $remainingTax = (float) $gst['tax_amount'];
        $remainingCgst = (float) $gst['cgst_amount'];
        $remainingSgst = (float) $gst['sgst_amount'];
        $remainingIgst = (float) $gst['igst_amount'];
        $last = count($lines) - 1;

        foreach ($lines as $i => &$line) {
            $share = ((float) $line['amount']) / $taxableTotal;
            if ($i === $last) {
                $line['tax'] = round($remainingTax, 2);
                $line['cgst'] = round($remainingCgst, 2);
                $line['sgst'] = round($remainingSgst, 2);
                $line['igst'] = round($remainingIgst, 2);
            } else {
                $line['tax'] = round($gst['tax_amount'] * $share, 2);
                $line['cgst'] = round($gst['cgst_amount'] * $share, 2);
                $line['sgst'] = round($gst['sgst_amount'] * $share, 2);
                $line['igst'] = round($gst['igst_amount'] * $share, 2);
                $remainingTax -= $line['tax'];
                $remainingCgst -= $line['cgst'];
                $remainingSgst -= $line['sgst'];
                $remainingIgst -= $line['igst'];
            }
            $line['tax_rate'] = $gst['tax_rate'];
            $line['cgst_rate'] = $gst['cgst_rate'];
            $line['sgst_rate'] = $gst['sgst_rate'];
            $line['igst_rate'] = $gst['igst_rate'];
        }
        unset($line);

        return $lines;
    }

    private function resolveSalespersonId(\DOMElement $node): ?int
    {
        foreach ($this->salespersonNamesFromVoucher($node) as $name) {
            $id = $this->ensureSalespersonUser($name);
            if ($id) {
                return $id;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function salespersonNamesFromVoucher(\DOMElement $node): array
    {
        $names = [];
        foreach ($node->getElementsByTagName('COSTCENTREALLOCATIONS.LIST') as $cc) {
            if (! $cc instanceof \DOMElement) {
                continue;
            }
            $name = $this->text($cc, 'NAME');
            if ($this->isUsableSalespersonName($name)) {
                $names[] = $this->normalizePersonName($name);
            }
        }

        $enteredBy = $this->text($node, 'ENTEREDBY');
        if ($this->isUsableSalespersonName($enteredBy)) {
            $names[] = $this->normalizePersonName($enteredBy);
        }

        return array_values(array_unique($names));
    }

    private function isUsableSalespersonName(string $name): bool
    {
        $name = trim($name);
        if ($name === '' || strlen($name) < 2) {
            return false;
        }
        $upper = strtoupper($name);
        $blocked = [
            'ENTRY', 'NOT APPLICABLE', 'PRIMARY', 'PRIMARY BATCH', 'MAIN LOCATION',
            'ADMIN', 'SYSTEM', 'NA', 'N/A', 'NONE', 'DEFAULT',
        ];

        return ! in_array($upper, $blocked, true)
            && ! str_starts_with($upper, '&#')
            && ! preg_match('/^\d+$/', $name);
    }

    private function normalizePersonName(string $name): string
    {
        $name = html_entity_decode(trim($name), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        return trim($name);
    }

    private function ensureSalespersonUser(string $name): ?int
    {
        $name = $this->normalizePersonName($name);
        if (! $this->isUsableSalespersonName($name)) {
            return null;
        }

        $user = User::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if (! $user) {
            // Loose match: "MR KAPIL DHIR" ↔ "Kapil Dhir"
            $compact = mb_strtolower(preg_replace('/\b(mr|mrs|ms|ji)\b\.?/i', '', $name) ?? $name);
            $compact = trim(preg_replace('/\s+/', ' ', $compact) ?? $compact);
            if ($compact !== '') {
                $user = User::query()
                    ->whereRaw('LOWER(name) LIKE ?', ['%'.$compact.'%'])
                    ->first();
            }
        }

        if (! $user) {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '.', $name) ?: 'salesperson');
            $email = $slug.'@tally-import.local';
            $suffix = 1;
            while (User::query()->where('email', $email)->exists()) {
                $email = $slug.$suffix.'@tally-import.local';
                $suffix++;
            }

            $user = User::query()->create([
                'company_id' => $this->organization->id,
                'name' => $name,
                'email' => $email,
                'password' => bcrypt(str()->random(24)),
            ]);
            $this->counts['created']++;
        }

        try {
            if (method_exists($user, 'hasRole') && ! $user->hasRole('salesperson')) {
                $user->assignRole('salesperson');
            }
        } catch (\Throwable) {
            // Role may not exist in every tenant — still usable via getSalespersons fallbacks.
        }

        if ($user->employee()->exists()) {
            $user->employee()->update(['is_salesperson' => true]);
        }

        return (int) $user->id;
    }

    private function defaultSalespersonId(): int
    {
        static $id = null;
        if ($id !== null) {
            return $id;
        }

        $id = (int) (User::query()->orderBy('id')->value('id') ?: 0);
        if ($id < 1) {
            throw new \RuntimeException('No DMS user exists to assign as salesperson on imported sales orders.');
        }

        return $id;
    }

    private function ensureParty(string $name, string $role): ?Customer
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $party = Customer::query()
            ->where('company_id', $this->organization->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        $partyType = $role === 'supplier' ? 'supplier' : 'customer';
        if ($party) {
            return $party;
        }

        $base = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'PARTY', 0, 16));
        if ($base === '') {
            $base = 'PARTY';
        }
        $code = $base;
        $suffix = 1;
        while (Customer::query()->where('code', $code)->exists()) {
            $code = $base.str_pad((string) $suffix, 3, '0', STR_PAD_LEFT);
            $suffix++;
            if ($suffix > 999) {
                $code = 'P'.substr((string) time(), -9).random_int(10, 99);
                break;
            }
        }

        return Customer::query()->create([
            'company_id' => $this->organization->id,
            'name' => $name,
            'code' => $code,
            'party_type' => $partyType,
            'is_active' => true,
        ]);
    }

    private function ensureProduct(string $name, string $unitName): Product
    {
        $product = Product::query()
            ->where('company_id', $this->organization->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();
        if ($product) {
            return $product;
        }

        $uom = Uom::query()
            ->where(function ($q) use ($unitName) {
                $q->whereRaw('LOWER(code) = ?', [mb_strtolower($unitName)])
                    ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($unitName)]);
            })
            ->first();
        if (! $uom) {
            $uom = Uom::query()->create([
                'name' => $unitName,
                'code' => $unitName,
                'is_active' => true,
            ]);
        }

        return Product::query()->create([
            'company_id' => $this->organization->id,
            'name' => $name,
            'sku' => \App\Support\CodeGenerator::forProduct($this->organization->id),
            'serial_no' => \App\Support\CodeGenerator::forProductSerial(),
            'base_uom_id' => $uom->id,
            'supply_type' => 'goods',
            'is_active' => true,
        ]);
    }

    private function ensureWarehouse(string $name): Warehouse
    {
        $warehouse = Warehouse::query()
            ->where('company_id', $this->organization->id)
            ->where(function ($q) use ($name) {
                $q->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->orWhereRaw('LOWER(code) = ?', [mb_strtolower($name)]);
            })
            ->first();
        if ($warehouse) {
            return $warehouse;
        }

        return Warehouse::query()->create([
            'company_id' => $this->organization->id,
            'name' => $name,
            'code' => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'WH', 0, 12)),
            'is_active' => true,
        ]);
    }

    private function ensureFinancialYear(string $date): void
    {
        $exists = FinancialYear::query()
            ->where('company_id', $this->organization->id)
            ->whereDate('starts_on', '<=', $date)
            ->whereDate('ends_on', '>=', $date)
            ->exists();
        if ($exists) {
            return;
        }

        $carbon = \Illuminate\Support\Carbon::parse($date);
        $start = $carbon->month >= 4
            ? $carbon->copy()->month(4)->day(1)->startOfDay()
            : $carbon->copy()->subYear()->month(4)->day(1)->startOfDay();
        $end = $start->copy()->addYear()->subDay();

        FinancialYear::query()->create([
            'company_id' => $this->organization->id,
            'name' => $start->format('Y').'-'.$end->format('y'),
            'starts_on' => $start->toDateString(),
            'ends_on' => $end->toDateString(),
            'is_current' => true,
            'is_closed' => false,
        ]);
        $this->counts['created']++;
    }

    private function map(string $tallyType, string $class, int $id, \DOMElement $node, string $name): void
    {
        $model = $class::query()->find($id);
        if (! $model) {
            return;
        }
        app(TallySyncMappingService::class)->createOrUpdate(
            $model,
            $class,
            $tallyType,
            $this->text($node, 'GUID') ?: null,
            $name,
            'synced'
        );
        \App\Domains\TallySync\Support\InboundActivityLog::item('vouchers', 'synced', $name);
    }

    private function voucherNumber(\DOMElement $node): string
    {
        return trim($this->text($node, 'VOUCHERNUMBER') ?: $node->getAttribute('VCHNUMBER'));
    }

    private function partyAmount(\DOMElement $node): float
    {
        foreach ($node->getElementsByTagName('ALLLEDGERENTRIES.LIST') as $entry) {
            if (! $entry instanceof \DOMElement) {
                continue;
            }
            if ($this->yes($this->text($entry, 'ISPARTYLEDGER'))) {
                $amount = $this->leadingNumber($this->text($entry, 'AMOUNT'));
                if (abs($amount) >= 0.01) {
                    return $amount;
                }
            }
        }

        foreach ($node->getElementsByTagName('ALLLEDGERENTRIES.LIST') as $entry) {
            if (! $entry instanceof \DOMElement) {
                continue;
            }
            $amount = $this->leadingNumber($this->text($entry, 'AMOUNT'));
            if (abs($amount) >= 0.01) {
                return $amount;
            }
        }

        return $this->leadingNumber($this->text($node, 'AMOUNT'));
    }

    private function leadingNumber(string $text): float
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace([',', '₹', 'Rs.', 'Rs', 'INR', ' '], '', $text);
        $text = preg_replace('/[^\d.\-]/', '', $text) ?? '';
        if ($text === '' || $text === '-' || $text === '.') {
            return 0.0;
        }
        if (preg_match('/^-?\d+(?:\.\d+)?/', $text, $m)) {
            return (float) $m[0];
        }

        return 0.0;
    }

    private function date(string $value): ?string
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';
        if (strlen($digits) >= 8) {
            return substr($digits, 0, 4).'-'.substr($digits, 4, 2).'-'.substr($digits, 6, 2);
        }

        return null;
    }

    private function text(\DOMElement $node, string $tag): string
    {
        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMElement && strcasecmp($child->tagName, $tag) === 0) {
                return trim($child->textContent ?? '');
            }
        }

        return '';
    }

    private function yes(string $value): bool
    {
        return in_array(mb_strtolower(trim($value)), ['yes', 'y', 'true', '1'], true);
    }
}
