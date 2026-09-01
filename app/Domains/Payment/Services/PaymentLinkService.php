<?php

namespace App\Domains\Payment\Services;

use App\Domains\Payment\Models\PaymentLink;
use App\Domains\Sales\Models\Invoice;
use Illuminate\Support\Str;

class PaymentLinkService
{
    public function createUpiLink(Invoice $invoice, ?int $expiresInHours = 48): PaymentLink
    {
        return $this->createForInvoice($invoice, $expiresInHours);
    }

    public function createForInvoice(Invoice $invoice, ?int $expiresInHours = 48): PaymentLink
    {
        $amount = max(0, (float) $invoice->grand_total - (float) $invoice->paid_amount);
        $existing = $invoice->paymentLinks()
            ->where('status', 'active')
            ->where('amount', $amount)
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $token = Str::random(32);

        return PaymentLink::create([
            'invoice_id' => $invoice->id,
            'token' => $token,
            'url' => route('payment-links.pay', ['token' => $token]),
            'provider' => config('services.payment.provider', 'internal'),
            'amount' => $amount,
            'status' => 'active',
            'expires_at' => now()->addHours($expiresInHours),
        ]);
    }
}
