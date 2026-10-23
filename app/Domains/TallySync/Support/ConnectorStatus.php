<?php

namespace App\Domains\TallySync\Support;

use App\Domains\Tally\Models\TallySyncQueue;
use Illuminate\Support\Facades\Schema;

class ConnectorStatus
{
    /**
     * @return array<string, mixed>
     */
    public static function snapshot(): array
    {
        $queueReady = Schema::hasTable('tally_sync_queues');
        $pending = $queueReady
            ? TallySyncQueue::query()->where('status', 'pending')->count()
            : 0;
        $failed = $queueReady
            ? TallySyncQueue::query()->where('status', 'failed')->count()
            : 0;

        return [
            'enabled' => SyncGate::enabled(),
            'use_connector' => (bool) config('tally_sync.use_connector', true),
            'company' => (string) config('tally_sync.company', config('services.tally.company', '')),
            'endpoint' => (string) config('tally_sync.endpoint', config('services.tally.endpoint', '')),
            'token_configured' => filled(config('tally_sync.connector_token', config('services.tally.connector_token'))),
            'api_exposed' => SyncGate::enabled() && (bool) config('tally_sync.expose_connector_api', true),
            'auto_enqueue' => SyncGate::enabled() && (bool) config('tally_sync.auto_enqueue', true),
            'pending' => $pending,
            'failed' => $failed,
            'templates' => [
                'BooksVoucherTemplate',
                'MasterTemplates',
                'XmlEnvelope',
                'XmlHelpers',
            ],
            'enable_steps' => [
                'In TallyPrime open the company, then Exchange, Data Synchronization, and set this computer as Server on port 9000.',
                'Set TALLY_SYNC_ENABLED=true, TALLY_CONNECTOR_TOKEN, and TALLY_COMPANY (the exact Tally company name) in .env.',
                'Run php artisan config:clear.',
                'On the Tally PC, put config.json next to tally_connector.exe and run it. It polls every 5 to 10 seconds.',
            ],
        ];
    }
}
