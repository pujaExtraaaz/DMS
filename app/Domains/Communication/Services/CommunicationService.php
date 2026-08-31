<?php

namespace App\Domains\Communication\Services;

use App\Domains\Communication\Models\CommunicationLog;
use App\Domains\Payment\Services\PaymentLinkService;
use App\Domains\Sales\Models\Invoice;
use App\Models\User;

class CommunicationService
{
    public function __construct(
        protected PaymentLinkService $paymentLinkService,
    ) {}

    public function sendInvoiceWhatsapp(Invoice $invoice, ?User $sentBy = null): CommunicationLog
    {
        $invoice->load('customer');

        // Stub WhatsApp integration — logs the attempt for now.
        return CommunicationLog::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'type' => 'whatsapp_invoice',
            'recipient' => $invoice->customer->phone ?? '',
            'status' => 'sent',
            'payload' => [
                'invoice_no' => $invoice->invoice_no,
                'grand_total' => $invoice->grand_total,
                'channel' => 'whatsapp',
            ],
            'sent_by' => $sentBy?->id,
        ]);
    }

    public function sendPaymentLink(Invoice $invoice, ?User $sentBy = null): CommunicationLog
    {
        $invoice->load('customer');
        $paymentLink = $this->paymentLinkService->createUpiLink($invoice);

        return CommunicationLog::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'type' => 'payment_link',
            'recipient' => $invoice->customer->phone ?? '',
            'status' => 'sent',
            'payload' => [
                'payment_link' => $paymentLink->url,
                'amount' => $paymentLink->amount,
            ],
            'sent_by' => $sentBy?->id,
        ]);
    }

    public function sendPaymentReminder(Invoice $invoice, ?User $sentBy = null): CommunicationLog
    {
        $invoice->load('customer');
        $outstanding = (float) $invoice->grand_total - (float) $invoice->paid_amount;

        // Stub payment reminder — logs the attempt for now.
        return CommunicationLog::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'type' => 'payment_reminder',
            'recipient' => $invoice->customer->phone ?? '',
            'status' => 'sent',
            'payload' => [
                'invoice_no' => $invoice->invoice_no,
                'outstanding' => $outstanding,
                'channel' => 'whatsapp',
            ],
            'sent_by' => $sentBy?->id,
        ]);
    }
}
