<?php

namespace App\Domains\Payment\Services;

use App\Domains\Payment\Models\PaymentLink;
use App\Domains\Sales\Models\Invoice;
use Illuminate\Support\Str;

class PaymentLinkService
{
    public function createUpiLink(Invoice $invoice, ?int $expiresInHours = 48): PaymentLink
    {
        $token = Str::random(32);
        $amount = (float) $invoice->grand_total - (float) $invoice->paid_amount;

        // Stub UPI payment link — replace with real gateway integration.
        $url = sprintf(
            'upi://pay?pa=merchant@upi&pn=DMS&am=%.2f&tn=Invoice%%20%s&tr=%s',
            $amount,
            $invoice->invoice_no,
            $token,
        );

        return PaymentLink::create([
            'invoice_id' => $invoice->id,
            'token' => $token,
            'url' => $url,
            'amount' => $amount,
            'status' => 'active',
            'expires_at' => now()->addHours($expiresInHours),
        ]);
    }
}
