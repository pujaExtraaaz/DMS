<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Tally\Accounting\RecurringVoucherService;

class RunRecurringVouchersCommand extends Command
{
    protected $signature = 'books:run-recurring {--date= : Run as of YYYY-MM-DD}';

    protected $description = 'Generate due recurring Books vouchers';

    public function handle(RecurringVoucherService $service): int
    {
        $date = $this->option('date')
            ? \Carbon\Carbon::parse($this->option('date'))
            : null;

        $created = $service->runDue($date);
        $this->info('Generated '.count($created).' voucher(s).');

        return self::SUCCESS;
    }
}
