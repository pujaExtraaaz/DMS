<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Tally\Receivables\DeferredRevenueService;

class RecognizeDeferredRevenueCommand extends Command
{
    protected $signature = 'books:recognize-deferred-revenue {--date=}';

    protected $description = 'Post monthly deferred revenue recognition journals';

    public function handle(DeferredRevenueService $service): int
    {
        $done = $service->recognizeDue($this->option('date') ?: null);
        $this->info(count($done).' schedule(s) recognized.');

        return self::SUCCESS;
    }
}
