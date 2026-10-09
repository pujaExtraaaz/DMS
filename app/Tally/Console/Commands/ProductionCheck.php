<?php

namespace Tally\Console\Commands;

use Illuminate\Console\Command;

class ProductionCheck extends Command
{
    protected $signature = 'app:production-check';

    protected $description = 'Report whether the application settings are safe for production';

    public function handle(): int
    {
        $production = app()->environment('production');
        $checks = [
            'APP_ENV' => ['value' => (string) config('app.env'), 'ok' => true],
            'APP_DEBUG' => ['value' => config('app.debug') ? 'true' : 'false', 'ok' => ! config('app.debug')],
            'APP_KEY' => ['value' => config('app.key') ? 'set' : 'missing', 'ok' => (string) config('app.key') !== ''],
            'APP_URL' => ['value' => (string) config('app.url'), 'ok' => ! str_contains((string) config('app.url'), 'localhost') || ! $production],
            'LOG_LEVEL' => ['value' => (string) config('logging.channels.single.level'), 'ok' => true],
            'DB_CONNECTION' => ['value' => (string) config('database.default'), 'ok' => true],
            'CACHE_STORE' => ['value' => (string) config('cache.default'), 'ok' => ! $production || config('cache.default') !== 'array'],
            'QUEUE_CONNECTION' => ['value' => (string) config('queue.default'), 'ok' => ! $production || config('queue.default') !== 'sync'],
            'SESSION_DRIVER' => ['value' => (string) config('session.driver'), 'ok' => config('session.driver') !== 'array' || ! $production],
            'SESSION_SECURE_COOKIE' => ['value' => config('session.secure') ? 'true' : 'false', 'ok' => ! $production || (bool) config('session.secure')],
            'BACKUP_DISK' => ['value' => (string) config('operations.backup_disk'), 'ok' => true],
            'WEBHOOK_QUEUE' => ['value' => config('operations.queue_webhooks') ? 'true' : 'false', 'ok' => true],
        ];

        $failed = false;

        foreach ($checks as $name => $check) {
            $mark = $check['ok'] ? 'ok' : 'fix';
            $this->line(sprintf('%-24s %-12s %s', $name, $mark, $check['value']));

            if (! $check['ok'] && $production) {
                $failed = true;
            }
        }

        if (! $production) {
            $this->info('APP_ENV is not production. Debug settings are allowed here.');

            return self::SUCCESS;
        }

        if ($failed) {
            $this->error('Production settings need attention. See the rows marked fix.');

            return self::FAILURE;
        }

        $this->info('Production settings look safe.');

        return self::SUCCESS;
    }
}
