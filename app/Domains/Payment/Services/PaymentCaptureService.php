<?php

namespace App\Domains\Payment\Services;

use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\PaymentLink;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentCaptureService
{
    public function __construct(protected OutstandingLedgerService $outstandingLedgerService) {}

    /** Records a gateway-confirmed payment exactly once. */
    public function capture(PaymentLink $link, string $reference, ?array $payload = null): Payment
    {
        return DB::transaction(function () use ($link, $reference, $payload) {
            $link = PaymentLink::query()->with('invoice')->lockForUpdate()->findOrFail($link->id);
            $existing = Payment::where('reference_no', $reference)->first();
            if ($existing) {
                return $existing;
            }

            $invoice = $link->invoice;
            abort_unless($invoice, 422, 'Payment link has no invoice.');
            $due = max(0, (float) $invoice->grand_total - (float) $invoice->paid_amount);
            $amount = min($due, (float) $link->amount);
            abort_if($amount <= 0, 422, 'This invoice has already been paid.');

            $date = now()->format('Ymd');
            $last = Payment::where('payment_no', 'like', "PAY-{$date}-%")->lockForUpdate()->orderByDesc('payment_no')->value('payment_no');
            $sequence = $last ? (int) Str::afterLast($last, '-') + 1 : 1;

            $payment = Payment::create([
                'payment_no' => sprintf('PAY-%s-%04d', $date, $sequence),
                'reference_no' => $reference,
                'customer_id' => $invoice->customer_id,
                'invoice_id' => $invoice->id,
                'paid_at' => now(),
                'method' => 'upi',
                'amount' => $amount,
                'status' => 'completed',
                'notes' => "Payment received through payment link {$link->token}",
                'recorded_by' => $invoice->salesperson_id,
            ]);

            $paid = min((float) $invoice->grand_total, (float) $invoice->paid_amount + $amount);
            $invoice->update([
                'paid_amount' => $paid,
                'status' => $paid >= (float) $invoice->grand_total ? 'paid' : 'partial',
            ]);
            $link->update([
                'status' => 'paid',
                'provider_reference' => $reference,
                'paid_at' => now(),
                'webhook_payload' => $payload,
            ]);
            $this->outstandingLedgerService->recordPayment($payment);

            return $payment;
        });
    }
}
