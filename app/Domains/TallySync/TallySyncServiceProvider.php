<?php

namespace App\Domains\TallySync;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Inventory\Models\StockAdjustment;
use App\Domains\Inventory\Models\StockTransfer;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\PartyAddress;
use App\Domains\Master\Models\PartyBankAccount;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductType;
use App\Domains\Master\Models\TaxRate;
use App\Domains\Master\Models\Uom;
use App\Domains\Order\Models\Order;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Payment\Models\Payment;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseInward;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\Quotation;
use App\Domains\Tally\Observers\TallyAutoEnqueueObserver;
use App\Domains\TallySync\Support\SyncGate;
use Illuminate\Support\ServiceProvider;

/**
 * Keeps TallyPrime sync/connector wiring in one place.
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
                Order::class,
                Quotation::class,
                PurchaseInward::class,
                StockTransfer::class,
                StockAdjustment::class,
                Product::class,
                Customer::class,
                Uom::class,
                Warehouse::class,
                Brand::class,
                Category::class,
                SubCategory::class,
                ProductType::class,
                Branch::class,
                TaxRate::class,
                PartyAddress::class,
                PartyBankAccount::class,
            ] as $model) {
                $model::observe(TallyAutoEnqueueObserver::class);
            }
        }
    }
}
