<?php

namespace App\Notifications;

use App\Domains\Sales\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InvoiceSystemNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Invoice $invoice,
        public string $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_no' => $this->invoice->invoice_no,
            'message' => $this->message,
        ];
    }
}
