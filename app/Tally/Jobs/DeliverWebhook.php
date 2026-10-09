<?php

namespace Tally\Jobs;

use Tally\Integration\WebhookDispatcher;
use Tally\Models\WebhookDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeliverWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $deliveryId) {}

    public function handle(WebhookDispatcher $dispatcher): void
    {
        $delivery = WebhookDelivery::query()->find($this->deliveryId);

        if (! $delivery) {
            return;
        }

        $dispatcher->attempt($delivery);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Webhook delivery job failed.', [
            'delivery_id' => $this->deliveryId,
            'error' => $exception->getMessage(),
        ]);
    }
}
