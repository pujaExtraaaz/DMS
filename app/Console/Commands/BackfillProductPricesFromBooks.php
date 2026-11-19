<?php

namespace App\Console\Commands;

use App\Support\ProductPricesFromBooks;
use Illuminate\Console\Command;

class BackfillProductPricesFromBooks extends Command
{
    protected $signature = 'dms:backfill-product-prices {--company= : Books company id filter}';

    protected $description = 'Fill DMS product prices and base UOM rows from Books rates / opening rates';

    public function handle(): int
    {
        $company = $this->option('company');
        $stats = ProductPricesFromBooks::sync($company !== null && $company !== '' ? (int) $company : null);
        $this->info("Product prices backfill: updated={$stats['updated']} uoms={$stats['uoms']} skipped={$stats['skipped']}");

        return self::SUCCESS;
    }
}
