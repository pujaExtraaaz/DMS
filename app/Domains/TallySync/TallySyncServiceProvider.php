<?php

namespace App\Domains\TallySync;

use App\Domains\Tally\Observers\TallyAutoEnqueueObserver;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Payment\Models\Payment;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Sales\Models\Invoice;
use App\Domains\TallySync\Support\SyncGate;
use Illuminate\Support\ServiceProvider;

/**
 * Keeps TallyPrime sync/connector wiring in one place for future re-enablement.
 * While tally_sync.enabled is false, only config + templates are active.
 */
class TallySyncServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../../config/tally_sync.php', 'tally_sync');
    }

    public function boot(): void
    {
        if (! SyncGate::enabled()) {
            return;
        }

        if (config('tally_sync.auto_enqueue', true)) {
            foreach ([
                Invoice::class,
                Payment::class,
                CreditNote::class,
                PurchaseInvoice::class,
                PurchaseOrder::class,
                Product::class,
                Customer::class,
                Uom::class,
            ] as $model) {
                $model::observe(TallyAutoEnqueueObserver::class);
            }
        }
    }
}
