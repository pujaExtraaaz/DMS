<?php

namespace App\Jobs;

use App\Domains\Tally\Models\TallySyncQueue;
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

        $item->increment('attempts');

        try {
            $endpoint = config('services.tally.endpoint');

            if (! $endpoint) {
                // No Tally endpoint configured — mark sent locally so queue is not stuck.
                $item->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'last_error' => null,
                ]);

                return;
            }

            $response = Http::timeout(20)
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
