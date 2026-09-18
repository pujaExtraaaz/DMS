<?php

namespace App\Domains\Communication\Services;

use App\Domains\Communication\Models\CommunicationLog;
use App\Domains\Payment\Services\PaymentLinkService;
use App\Domains\Sales\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Notifications\InvoiceSystemNotification;
use Throwable;

class CommunicationService
{
    public function __construct(
        protected PaymentLinkService $paymentLinkService,
    ) {}

    public function sendInvoiceWhatsapp(Invoice $invoice, ?User $sentBy = null): CommunicationLog
    {
        return $this->dispatch($invoice, 'whatsapp_invoice', 'whatsapp', function (Invoice $invoice) {
            $link = null;
            try {
                $link = $this->paymentLinkService->createUpiLink($invoice)->url;
            } catch (Throwable) {
                // optional
            }

            return [
                'invoice_no' => $invoice->invoice_no,
                'grand_total' => $invoice->grand_total,
                'message' => sprintf(
                    'Invoice %s for ₹%s is ready.%s',
                    $invoice->invoice_no,
                    number_format((float) $invoice->grand_total, 2),
                    $link ? " Pay: {$link}" : ''
                ),
            ];
        }, $sentBy);
    }

    public function sendInvoiceEmail(Invoice $invoice, ?User $sentBy = null): CommunicationLog
    {
        return $this->dispatch($invoice, 'email_invoice', 'email', function (Invoice $invoice) {
            return [
                'invoice_no' => $invoice->invoice_no,
                'grand_total' => $invoice->grand_total,
                'subject' => 'Invoice '.$invoice->invoice_no,
                'body' => sprintf(
                    "Dear %s,\n\nPlease find invoice %s totaling ₹%s.\nDue date: %s\n\nThank you.",
                    $invoice->customer?->name ?? 'Customer',
                    $invoice->invoice_no,
                    number_format((float) $invoice->grand_total, 2),
                    optional($invoice->due_date)->format('d M Y') ?? 'N/A'
                ),
                'to' => $invoice->customer?->email,
            ];
        }, $sentBy, $invoice->customer?->email);
    }

    public function sendNotification(Invoice $invoice, string $message, ?User $sentBy = null): CommunicationLog
    {
        return $this->dispatch($invoice, 'notification', 'notification', function () use ($message) {
            return [
                'message' => $message,
            ];
        }, $sentBy, (string) ($invoice->customer?->phone ?: $invoice->customer?->email ?: 'system'));
    }

    public function sendPaymentLink(Invoice $invoice, ?User $sentBy = null): CommunicationLog
    {
        $invoice->load('customer');

        try {
            $paymentLink = $this->paymentLinkService->createUpiLink($invoice);
            $recipient = $invoice->customer->phone ?? $invoice->customer->email ?? '';

            $log = CommunicationLog::create([
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'type' => 'payment_link',
                'recipient' => $recipient,
                'status' => 'queued',
                'payload' => [
                    'payment_link' => $paymentLink->url,
                    'amount' => $paymentLink->amount,
                    'message' => sprintf('Pay invoice %s: %s', $invoice->invoice_no, $paymentLink->url),
                ],
                'sent_by' => $sentBy?->id,
            ]);

            if (! filled($recipient)) {
                throw new \RuntimeException('Missing recipient phone/email for payment link');
            }

            if ($invoice->customer->phone) {
                $this->sendWhatsapp($recipient, $log->payload['message']);
            } elseif ($invoice->customer->email) {
                $this->sendEmail($recipient, 'Payment link '.$invoice->invoice_no, $log->payload['message']);
            }

            $log->update(['status' => 'sent']);

            return $log->fresh();
        } catch (Throwable $e) {
            return CommunicationLog::create([
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'type' => 'payment_link',
                'recipient' => $invoice->customer->phone ?? '',
                'status' => 'failed',
                'payload' => ['error' => $e->getMessage()],
                'sent_by' => $sentBy?->id,
            ]);
        }
    }

    public function sendPaymentReminder(Invoice $invoice, ?User $sentBy = null): CommunicationLog
    {
        $outstanding = (float) $invoice->grand_total - (float) $invoice->paid_amount;

        return $this->dispatch($invoice, 'payment_reminder', 'whatsapp', function () use ($invoice, $outstanding) {
            return [
                'invoice_no' => $invoice->invoice_no,
                'outstanding' => $outstanding,
                'message' => sprintf(
                    'Reminder: Invoice %s has outstanding ₹%s. Due: %s.',
                    $invoice->invoice_no,
                    number_format($outstanding, 2),
                    optional($invoice->due_date)->format('d M Y') ?? 'N/A'
                ),
            ];
        }, $sentBy);
    }

    protected function dispatch(
        Invoice $invoice,
        string $type,
        string $channel,
        callable $payloadFactory,
        ?User $sentBy = null,
        ?string $recipient = null,
    ): CommunicationLog {
        $invoice->loadMissing('customer');
        $recipient ??= $invoice->customer->phone ?? '';

        $payload = array_merge($payloadFactory($invoice), ['channel' => $channel, 'provider' => $this->providerName($channel)]);

        $log = CommunicationLog::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'type' => $type,
            'recipient' => $recipient,
            'status' => 'queued',
            'payload' => $payload,
            'sent_by' => $sentBy?->id,
        ]);

        try {
            if (! filled($recipient)) {
                throw new \RuntimeException('Missing recipient for '.$channel);
            }

            match ($channel) {
                'whatsapp' => $this->sendWhatsapp($recipient, (string) ($payload['message'] ?? ('Invoice '.$invoice->invoice_no))),
                'email' => $this->sendEmail(
                    $recipient,
                    (string) ($payload['subject'] ?? ('Invoice '.$invoice->invoice_no)),
                    (string) ($payload['body'] ?? ($payload['message'] ?? ''))
                ),
                'notification' => $this->sendInAppNotification($invoice, (string) ($payload['message'] ?? '')),
                default => null,
            };

            $log->update(['status' => 'sent']);
        } catch (Throwable $e) {
            $log->update([
                'status' => 'failed',
                'payload' => array_merge($log->payload ?? [], ['error' => $e->getMessage()]),
            ]);
            Log::warning('Communication dispatch failed', [
                'type' => $type,
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);
        }

        return $log->fresh();
    }

    protected function providerName(string $channel): string
    {
        return match ($channel) {
            'whatsapp' => config('services.whatsapp.token') ? 'meta_cloud_api' : 'log_fallback',
            'email' => config('mail.default'),
            default => 'database',
        };
    }

    protected function sendWhatsapp(string $to, string $message): void
    {
        $token = config('services.whatsapp.token');
        $phoneId = config('services.whatsapp.phone_number_id');
        $digits = preg_replace('/\D+/', '', $to) ?: $to;

        if (! $token || ! $phoneId) {
            Log::info('WhatsApp fallback (no credentials)', ['to' => $digits, 'message' => $message]);

            return;
        }

        $response = Http::withToken($token)
            ->post("https://graph.facebook.com/v19.0/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $digits,
                'type' => 'text',
                'text' => ['body' => $message],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('WhatsApp API '.$response->status().': '.$response->body());
        }
    }

    protected function sendEmail(string $to, string $subject, string $body): void
    {
        Mail::raw($body, function ($mail) use ($to, $subject) {
            $mail->to($to)->subject($subject);
        });
    }

    protected function sendInAppNotification(Invoice $invoice, string $message): void
    {
        $users = User::query()
            ->when($invoice->salesperson_id, fn ($q) => $q->where('id', $invoice->salesperson_id))
            ->limit(5)
            ->get();

        if ($users->isEmpty()) {
            Log::info('In-app notification', ['invoice' => $invoice->invoice_no, 'message' => $message]);

            return;
        }

        if (class_exists(InvoiceSystemNotification::class)) {
            try {
                Notification::send($users, new InvoiceSystemNotification($invoice, $message));
            } catch (Throwable $e) {
                Log::info('In-app notification fallback', [
                    'users' => $users->pluck('id')->all(),
                    'message' => $message,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            Log::info('In-app notification (no notification class)', [
                'users' => $users->pluck('id')->all(),
                'message' => $message,
            ]);
        }
    }
}
