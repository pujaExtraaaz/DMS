<?php

namespace App\Console\Commands;

use App\Support\StockLevelsFromBooks;
use Illuminate\Console\Command;

class BackfillStockLevelsFromBooks extends Command
{
    protected $signature = 'dms:backfill-stock-levels {--company= : Books company id filter}';

    protected $description = 'Fill DMS stock_levels from Books godown openings using product/warehouse sync links';

    public function handle(): int
    {
        $company = $this->option('company');
        $stats = StockLevelsFromBooks::sync($company !== null && $company !== '' ? (int) $company : null);
        $this->info("Stock levels backfill: created={$stats['created']} updated={$stats['updated']} skipped={$stats['skipped']}");

        return self::SUCCESS;
    }
}
