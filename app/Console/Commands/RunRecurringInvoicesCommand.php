<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Tally\Selling\RecurringInvoiceService;

class RunRecurringInvoicesCommand extends Command
{
    protected $signature = 'books:run-recurring-invoices {--date=}';

    protected $description = 'Create draft/posted sales invoices from due recurring invoice templates';

    public function handle(RecurringInvoiceService $service): int
    {
        $created = $service->runDue($this->option('date') ?: null);
        $this->info(count($created).' recurring invoice(s) created.');

        return self::SUCCESS;
    }
}
