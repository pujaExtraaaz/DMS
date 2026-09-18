<?php

namespace App\Domains\Payment\Services;

use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\PaymentAllocation;
use App\Domains\Sales\Models\Invoice;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentAllocationService
{
    public function __construct(protected OutstandingLedgerService $outstandingLedgerService) {}

    /**
     * @param  array<int, array{invoice_id: int, amount: float|int|string}>  $allocations
     */
    public function allocate(Payment $payment, array $allocations): Payment
    {
        return DB::transaction(function () use ($payment, $allocations) {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $total = round(collect($allocations)->sum(fn ($row) => (float) $row['amount']), 2);

            if ($total <= 0) {
                throw new InvalidArgumentException('Allocation total must be greater than zero.');
            }

            if ($total > (float) $locked->amount + 0.001) {
                throw new InvalidArgumentException('Allocation total exceeds payment amount.');
            }

            PaymentAllocation::query()->where('payment_id', $locked->id)->delete();

            $primaryInvoiceId = null;

            foreach ($allocations as $row) {
                $amount = round((float) $row['amount'], 2);
                if ($amount <= 0) {
                    continue;
                }

                $invoice = Invoice::query()->whereKey($row['invoice_id'])->lockForUpdate()->firstOrFail();

                if ((int) $invoice->customer_id !== (int) $locked->customer_id) {
                    throw new InvalidArgumentException('Invoice does not belong to the payment customer.');
                }

                $outstanding = round((float) $invoice->grand_total - (float) $invoice->paid_amount, 2);
                if ($amount > $outstanding + 0.001) {
                    throw new InvalidArgumentException("Allocation exceeds outstanding for invoice {$invoice->invoice_no}.");
                }

                PaymentAllocation::create([
                    'payment_id' => $locked->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $amount,
                ]);

                $newPaid = min((float) $invoice->grand_total, (float) $invoice->paid_amount + $amount);
                $invoice->update([
                    'paid_amount' => $newPaid,
                    'status' => $newPaid >= (float) $invoice->grand_total ? 'paid' : 'partial',
                ]);

                $primaryInvoiceId ??= $invoice->id;
            }

            $locked->update([
                'invoice_id' => $primaryInvoiceId,
                'status' => 'completed',
            ]);

            return $locked->fresh(['allocations.invoice', 'customer', 'invoice']);
        });
    }

    /**
     * Create a payment and allocate across one or more invoices in one step.
     *
     * @param  array<int, array{invoice_id: int, amount: float|int|string}>  $allocations
     */
    public function captureWithAllocations(array $paymentData, array $allocations): Payment
    {
        return DB::transaction(function () use ($paymentData, $allocations) {
            $payment = Payment::create([
                'payment_no' => $paymentData['payment_no'],
                'reference_no' => $paymentData['reference_no'] ?? null,
                'customer_id' => $paymentData['customer_id'],
                'invoice_id' => null,
                'amount' => $paymentData['amount'],
                'method' => $paymentData['method'] ?? 'cash',
                'status' => 'completed',
                'paid_at' => $paymentData['paid_at'] ?? now(),
                'recorded_by' => $paymentData['recorded_by'] ?? auth()->id(),
                'notes' => $paymentData['notes'] ?? null,
            ]);

            $this->outstandingLedgerService->recordPayment($payment);
            $this->allocate($payment, $allocations);

            return $payment->fresh(['allocations.invoice', 'customer', 'invoice']);
        });
    }
}
