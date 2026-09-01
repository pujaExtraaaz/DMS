<?php

namespace App\Http\Controllers\Payment;

use App\Domains\Payment\Models\PaymentLink;
use App\Domains\Payment\Services\PaymentCaptureService;
use App\Domains\Payment\Services\PaymentLinkService;
use App\Domains\Sales\Models\Invoice;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PaymentLinkController extends Controller
{
    public function __construct(
        protected PaymentLinkService $paymentLinkService,
        protected PaymentCaptureService $paymentCaptureService,
    ) {}

    public function create(Invoice $invoice): RedirectResponse
    {
        $link = $this->paymentLinkService->createForInvoice($invoice);

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
        abort_unless(app()->environment(['local', 'testing']) && config('services.payment.provider') === 'internal', 403);
        $paymentLink = PaymentLink::with('invoice.customer')->where('token', $token)->firstOrFail();

        if ($paymentLink->status !== 'paid') {
            $this->paymentCaptureService->capture($paymentLink, 'INTERNAL-'.strtoupper(Str::random(16)), ['source' => 'customer-payment-page']);
        }

        return $this->flashSuccess('Payment completed successfully. Invoice and customer ledger updated.', 'payment-links.pay', ['token' => $token]);
    }

    public function webhook(Request $request): \Illuminate\Http\JsonResponse
    {
        $secret = (string) config('services.payment.webhook_secret');
        $signature = (string) $request->header('X-Payment-Signature');
        abort_unless($secret !== '' && hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $signature), 401);

        $data = $request->validate([
            'reference' => 'required|string|max:100',
            'token' => 'required|string|size:32',
            'status' => 'required|in:paid,success',
        ]);
        $link = PaymentLink::with('invoice')->where('token', $data['token'])->firstOrFail();
        $payment = $this->paymentCaptureService->capture($link, $data['reference'], $request->all());

        return response()->json(['ok' => true, 'payment_id' => $payment->id]);
    }
}
