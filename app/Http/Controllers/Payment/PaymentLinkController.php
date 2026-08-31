<?php

namespace App\Http\Controllers\Payment;

use App\Domains\Payment\Models\PaymentLink;
use App\Domains\Payment\Services\PaymentLinkService;
use App\Domains\Sales\Models\Invoice;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentLinkController extends Controller
{
    public function __construct(
        protected PaymentLinkService $paymentLinkService,
    ) {}

    public function create(Invoice $invoice): RedirectResponse
    {
        $link = $this->paymentLinkService->createUpiLink($invoice);

        return $this->flashSuccess('Payment link created.', 'payment-links.pay', ['token' => $link->token]);
    }

    public function pay(string $token): View
    {
        $paymentLink = PaymentLink::with('invoice.customer')
            ->where('token', $token)
            ->firstOrFail();

        return view('payments.pay', compact('paymentLink'));
    }

    public function markPaid(Request $request, string $token): RedirectResponse
    {
        $paymentLink = PaymentLink::where('token', $token)->firstOrFail();
        $paymentLink->update(['status' => 'paid']);

        return $this->flashSuccess('Payment marked as received (stub).', 'payment-links.pay', ['token' => $token]);
    }
}
