<?php

namespace App\Console\Commands;

use App\Domains\Order\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshPendingOrdersCommand extends Command
{
    protected $signature = 'dms:refresh-pending-orders';

    protected $description = 'Stub: refresh pending / back-order pipeline counters';

    public function handle(): int
    {
        $pending = Order::query()->whereIn('status', ['pending', 'approved'])->count();
        $backOrder = Order::query()->where('back_order', true)->count();

        Log::info('dms:refresh-pending-orders', [
            'pending' => $pending,
            'back_order' => $backOrder,
        ]);

        $this->info("Pending-order refresh stub: pending={$pending}, back_order={$backOrder}");

        return self::SUCCESS;
    }
}
