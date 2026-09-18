<?php

namespace App\Console\Commands;

use App\Domains\Sales\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class IdentifyOverdueCommand extends Command
{
    protected $signature = 'dms:identify-overdue';

    protected $description = 'Identify overdue invoices for interest and credit risk';

    public function handle(): int
    {
        $count = Invoice::query()
            ->whereNotIn('status', ['cancelled', 'paid', 'draft'])
            ->whereColumn('paid_amount', '<', 'grand_total')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->count();

        Log::info('dms:identify-overdue', ['count' => $count]);
        $this->info("Identified {$count} overdue invoice(s).");

        return self::SUCCESS;
    }
}
