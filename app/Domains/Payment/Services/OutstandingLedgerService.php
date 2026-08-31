<?php

namespace App\Domains\Payment\Services;

use App\Domains\Payment\Models\OutstandingLedger;
use App\Domains\Payment\Models\Payment;
use App\Domains\Sales\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OutstandingLedgerService
{
    public function recordInvoice(Invoice $invoice): OutstandingLedger
    {
        return $this->recordEntry(
            customerId: $invoice->customer_id,
            type: 'invoice',
            reference: $invoice,
            debit: (float) $invoice->grand_total,
            credit: 0,
            notes: "Invoice {$invoice->invoice_no}",
        );
    }

    public function recordPayment(Payment $payment): OutstandingLedger
    {
        return $this->recordEntry(
            customerId: $payment->customer_id,
            type: 'payment',
            reference: $payment,
            debit: 0,
            credit: (float) $payment->amount,
            notes: "Payment {$payment->payment_no}",
        );
    }

    public function recordSettlement(int $customerId, float $outstandingAmount, ?Model $reference = null, ?string $notes = null): OutstandingLedger
    {
        return $this->recordEntry(
            customerId: $customerId,
            type: 'settlement',
            reference: $reference,
            debit: 0,
            credit: $outstandingAmount,
            notes: $notes ?? 'Settlement adjustment',
        );
    }

    public function recordAdjustment(int $customerId, float $debit, float $credit, ?string $notes = null): OutstandingLedger
    {
        return $this->recordEntry(
            customerId: $customerId,
            type: 'adjustment',
            reference: null,
            debit: $debit,
            credit: $credit,
            notes: $notes,
        );
    }

    public function getCurrentBalance(int $customerId): float
    {
        $balance = OutstandingLedger::query()
            ->where('customer_id', $customerId)
            ->orderByDesc('id')
            ->value('balance');

        return (float) ($balance ?? 0);
    }

    protected function recordEntry(
        int $customerId,
        string $type,
        ?Model $reference,
        float $debit,
        float $credit,
        ?string $notes = null,
    ): OutstandingLedger {
        return DB::transaction(function () use ($customerId, $type, $reference, $debit, $credit, $notes) {
            $previousBalance = OutstandingLedger::query()
                ->where('customer_id', $customerId)
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('balance');

            $previousBalance = (float) ($previousBalance ?? 0);
            $newBalance = $previousBalance + $debit - $credit;

            return OutstandingLedger::create([
                'customer_id' => $customerId,
                'type' => $type,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $newBalance,
                'notes' => $notes,
            ]);
        });
    }
}
