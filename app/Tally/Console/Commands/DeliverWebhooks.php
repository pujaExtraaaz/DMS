<?php

namespace Tally\Console\Commands;

use Tally\Integration\WebhookDispatcher;
use Illuminate\Console\Command;

class DeliverWebhooks extends Command
{
    protected $signature = 'webhooks:deliver {--queue : Dispatch each due delivery onto the queue}';

    protected $description = 'Retry webhook deliveries that are due';

    public function handle(WebhookDispatcher $dispatcher): int
    {
        if ($this->option('queue')) {
            $count = 0;

            \Tally\Models\WebhookDelivery::query()
                ->where('status', 'failed')
                ->where('attempts', '<', 5)
                ->where(function ($query): void {
                    $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now());
                })
                ->orderBy('id')
                ->limit(50)
                ->pluck('id')
                ->each(function (int $id) use (&$count): void {
                    \Tally\Jobs\DeliverWebhook::dispatch($id);
                    $count++;
                });

            $this->info('Queued '.$count.' webhook deliveries.');

            return self::SUCCESS;
        }

        $count = $dispatcher->deliverDue();
        $this->info('Processed '.$count.' webhook deliveries.');

        return self::SUCCESS;
    }
}
