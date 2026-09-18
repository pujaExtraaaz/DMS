<?php

namespace App\Console\Commands;

use App\Domains\Interest\Services\InterestService;
use App\Domains\Master\Models\Customer;
use App\Domains\Order\Models\Order;
use App\Domains\Sales\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshAgingCommand extends Command
{
    protected $signature = 'dms:refresh-aging';

    protected $description = 'Stub: refresh party aging buckets for MIS';

    public function handle(): int
    {
        $overdue = Invoice::query()
            ->whereNotIn('status', ['cancelled', 'paid', 'draft'])
            ->whereColumn('paid_amount', '<', 'grand_total')
            ->where(function ($q) {
                $q->whereNotNull('due_date')->whereDate('due_date', '<', now()->toDateString())
                    ->orWhere(function ($q2) {
                        $q2->whereNull('due_date')->whereDate('invoice_date', '<', now()->toDateString());
                    });
            })
            ->count();

        Log::info('dms:refresh-aging', ['overdue_invoices' => $overdue]);
        $this->info("Aging refresh stub complete. Overdue invoices: {$overdue}");

        return self::SUCCESS;
    }
}
