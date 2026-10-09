<?php

namespace Tally\Integration;

use Tally\Jobs\DeliverWebhook;
use Tally\Models\WebhookDelivery;
use Tally\Models\WebhookEndpoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WebhookDispatcher
{
    public const EVENTS = [
        'company.created',
        'company.updated',
        'ledger.created',
        'ledger.updated',
        'product.created',
        'product.updated',
        'party.created',
        'party.updated',
        'voucher.created',
        'voucher.posted',
        'voucher.cancelled',
        'stock_movement.created',
        'payment.posted',
        'receipt.posted',
        'integration.sync',
    ];

    /**
     * @param  array<string, mixed>  $data
     */
    public function record(string $event, array $data): void
    {
        if (! Schema::hasTable('acct_webhook_endpoints')) {
            return;
        }

        $send = function () use ($event, $data): void {
            $payload = [
                'event' => $event,
                'occurred_at' => now()->toIso8601String(),
                'data' => $data,
            ];

            WebhookEndpoint::query()
                ->where('is_active', true)
                ->where(function ($query) use ($event, $data): void {
                    $companyId = $this->companyId($event, $data);

                    if ($companyId === null) {
                        $query->whereNull('company_id');

                        return;
                    }

                    $query->where('company_id', $companyId);

                    if (in_array($event, ['company.created', 'company.updated'], true)) {
                        $query->orWhereNull('company_id');
                    }
                })
                ->get()
                ->each(function (WebhookEndpoint $endpoint) use ($event, $payload): void {
                    $events = $endpoint->events ?? [];

                    if (! in_array('*', $events, true) && ! in_array($event, $events, true)) {
                        return;
                    }

                    $delivery = $endpoint->deliveries()->create([
                        'event' => $event,
                        'payload' => $payload,
                        'status' => 'pending',
                    ]);

                    if (config('operations.queue_webhooks') && ! app()->runningUnitTests()) {
                        DeliverWebhook::dispatch($delivery->id);
                    } else {
                        $this->attempt($delivery);
                    }
                });
        };

        if (app()->runningUnitTests()) {
            $send();

            return;
        }

        DB::afterCommit($send);
    }

    public function attempt(WebhookDelivery $delivery): WebhookDelivery
    {
        $delivery->loadMissing('endpoint');
        $endpoint = $delivery->endpoint;

        if (! $endpoint || ! $delivery->canRetry()) {
            return $delivery;
        }

        if (! WebhookUrl::isAllowed((string) $endpoint->url)) {
            $this->markFailed($delivery, null, 'The endpoint address is private or not a public URL.');

            return $delivery->refresh();
        }

        $body = json_encode($delivery->payload, JSON_THROW_ON_ERROR);
        $delivery->increment('attempts');
        $delivery->refresh();

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Tally-Event' => $delivery->event,
                    'X-Tally-Delivery' => (string) $delivery->id,
                    'X-Tally-Signature' => hash_hmac('sha256', $body, (string) $endpoint->secret),
                ])
                ->withBody($body, 'application/json')
                ->post($endpoint->url);

            if ($response->successful()) {
                $delivery->update([
                    'status' => 'delivered',
                    'response_status' => $response->status(),
                    'last_error' => null,
                    'next_attempt_at' => null,
                    'delivered_at' => now(),
                ]);

                return $delivery->refresh();
            }

            $this->markFailed($delivery, $response->status(), 'The endpoint returned HTTP '.$response->status().'.');
        } catch (\Throwable $exception) {
            $this->markFailed($delivery, null, $exception->getMessage());
        }

        return $delivery->refresh();
    }

    public function deliverDue(): int
    {
        $count = 0;

        WebhookDelivery::query()
            ->where('status', 'failed')
            ->where('attempts', '<', 5)
            ->where(function ($query): void {
                $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now());
            })
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->each(function (WebhookDelivery $delivery) use (&$count): void {
                $this->attempt($delivery);
                $count++;
            });

        return $count;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function companyId(string $event, array $data): ?int
    {
        if (isset($data['company_id']) && is_numeric($data['company_id'])) {
            return (int) $data['company_id'];
        }

        if (str_starts_with($event, 'company.') && isset($data['id']) && is_numeric($data['id'])) {
            return (int) $data['id'];
        }

        return null;
    }

    private function markFailed(WebhookDelivery $delivery, ?int $status, string $error): void
    {
        $delivery->update([
            'status' => 'failed',
            'response_status' => $status,
            'last_error' => mb_substr($error, 0, 1000),
            'next_attempt_at' => now()->addMinutes(max(1, $delivery->attempts)),
        ]);

        Log::warning('Webhook delivery failed.', [
            'delivery_id' => $delivery->id,
            'endpoint_id' => $delivery->webhook_endpoint_id,
            'event' => $delivery->event,
            'status' => $status,
        ]);
    }
}
