<?php

namespace App\Jobs;

use App\Domains\TallySync\Bridge\TallyInbound;
use App\Domains\TallySync\Import\TallyImportLookupCache;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Tally\Models\Company;

/**
 * Process one pre-split VOUCHER chunk XML (point 5).
 */
class ImportTallyVoucherChunkJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 2;

    public function __construct(
        public string $chunkPath,
        public string $target,
        public string $companyName,
        public string $runId,
        public int $chunkIndex = 0,
    ) {
        $this->onQueue('tally-import');
    }

    public function handle(TallyInbound $inbound): void
    {
        if (! is_file($this->chunkPath)) {
            Log::warning('Tally voucher chunk missing', ['path' => $this->chunkPath]);

            return;
        }

        config(['tally_sync.company' => $this->companyName]);
        $inbound->setForceOverwrite(true);

        $books = Company::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($this->companyName)])
            ->where('is_active', true)
            ->first();
        if ($books && $this->target === 'books') {
            $cache = new TallyImportLookupCache;
            $cache->warmBooks($books);
            $inbound->setLookupCache($cache);
        }

        $xml = File::get($this->chunkPath);
        $result = $inbound->apply('vouchers', $xml, $this->target);
        unset($xml);

        $key = 'tally-import:'.$this->runId.':'.$this->target;
        Cache::lock($key.':lock', 10)->block(5, function () use ($key, $result) {
            $agg = Cache::get($key, ['created' => 0, 'updated' => 0, 'skipped' => 0, 'conflicts' => 0, 'chunks' => 0]);
            $agg['created'] += (int) ($result['created'] ?? 0);
            $agg['updated'] += (int) ($result['updated'] ?? 0);
            $agg['skipped'] += (int) ($result['skipped'] ?? 0);
            $agg['conflicts'] += (int) ($result['conflicts'] ?? 0);
            $agg['chunks']++;
            Cache::put($key, $agg, now()->addHours(12));
        });
    }
}
