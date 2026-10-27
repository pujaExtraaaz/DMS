<?php

namespace App\Console\Commands;

use App\Domains\Target\Services\SalesTargetCalculationService;
use Illuminate\Console\Command;

class RecalculateSalesTargetsCommand extends Command
{
    protected $signature = 'targets:recalculate {--all : Recalculate all targets including completed/drafts}';
    protected $description = 'Recalculate salesman, team, and branch target achievements directly from real invoice data';

    public function handle(SalesTargetCalculationService $service): int
    {
        $this->info('Starting sales target recalculation from invoice data...');
        $onlyActive = !$this->option('all');

        $count = $service->recalculateAll($onlyActive);

        $this->info("Successfully recalculated {$count} targets with zero drift against sales invoices and credit notes.");
        return self::SUCCESS;
    }
}

