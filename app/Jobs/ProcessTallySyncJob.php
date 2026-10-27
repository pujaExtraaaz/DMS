<?php

namespace App\Jobs;

use App\Domains\Tally\Models\TallySyncQueue;
use App\Domains\TallySync\Support\SyncGate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessTallySyncJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $queueId) {}

    public function handle(): void
    {
        $item = TallySyncQueue::query()->find($this->queueId);
        if (! $item || in_array($item->status, ['sent', 'skipped'], true)) {
            return;
        }

        if (! SyncGate::enabled()) {
            $item->update([
                'status' => 'skipped',
                'sent_at' => now(),
                'last_error' => 'TallyPrime sync disabled (native Books is live). Set TALLY_SYNC_ENABLED=true to process.',
            ]);

            return;
        }

        // Office bridge mode: leave items pending for the local Tally Connector to pull.
        if (config('tally_sync.use_connector', true)) {
            return;
        }

        $item->increment('attempts');

        try {
            $endpoint = config('services.tally.endpoint');

            if (! $endpoint) {
                $item->update([
                    'status' => 'skipped',
                    'sent_at' => now(),
                    'last_error' => 'TALLY_ENDPOINT not configured — marked skipped. Enable TALLY_USE_CONNECTOR=true and run the office bridge, or set TALLY_ENDPOINT.',
                ]);

                return;
            }

            $response = Http::timeout(20)
                ->withHeaders([
                    'Content-Type' => 'application/xml',
                    'X-Tally-Company' => (string) config('services.tally.company'),
                ])
                ->withBody($item->payload ?? '', 'application/xml')
                ->post($endpoint);

            if (! $response->successful()) {
                throw new \RuntimeException('Tally HTTP '.$response->status().': '.$response->body());
            }

            $item->update([
                'status' => 'sent',
                'sent_at' => now(),
                'last_error' => null,
            ]);
        } catch (Throwable $e) {
            $item->update([
                'status' => 'failed',
                'last_error' => $e->getMessage(),
            ]);
            Log::warning('Tally sync failed', ['id' => $item->id, 'error' => $e->getMessage()]);
        }
    }
}
