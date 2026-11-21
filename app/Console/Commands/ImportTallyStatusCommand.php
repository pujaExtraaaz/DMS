<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ImportTallyStatusCommand extends Command
{
    protected $signature = 'dms:import-tally-status {runId : Run id printed when --queue-vouchers was used}';

    protected $description = 'Show aggregated progress for queued Tally voucher chunk jobs';

    public function handle(): int
    {
        $runId = (string) $this->argument('runId');
        foreach (['dms', 'books'] as $target) {
            $key = 'tally-import:'.$runId.':'.$target;
            $agg = Cache::get($key);
            if (! $agg) {
                $this->line("{$target}: (no cache yet)");

                continue;
            }
            $this->line(sprintf(
                '%s: created=%d updated=%d skipped=%d conflicts=%d chunks_done=%d',
                $target,
                $agg['created'] ?? 0,
                $agg['updated'] ?? 0,
                $agg['skipped'] ?? 0,
                $agg['conflicts'] ?? 0,
                $agg['chunks'] ?? 0
            ));
        }

        return self::SUCCESS;
    }
}
