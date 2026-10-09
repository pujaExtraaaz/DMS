<?php

namespace App\Providers;

use App\Domains\Communication\Services\CommunicationService;
use App\Domains\Inventory\Services\StockMovementService;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Master\Observers\ProductPriceObserver;
use App\Domains\Master\Services\PriceMasterService;
use App\Domains\Master\Services\ProductDiscountService;
use App\Domains\Banking\Models\BankAccountTransaction;
use App\Domains\Banking\Models\OdAccount;
use App\Domains\Inventory\Models\StockAdjustment;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Services\OrderConversionService;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Services\OutstandingLedgerService;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Payment\Services\PaymentLinkService;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Services\InvoiceNumberGenerator;
use App\Domains\Sync\Observers\BooksSyncObserver;
use App\Policies\OrderPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PriceMasterService::class);
        $this->app->singleton(StockMovementService::class);
        $this->app->singleton(OutstandingLedgerService::class);
        $this->app->singleton(InvoiceNumberGenerator::class);
        $this->app->singleton(PaymentLinkService::class);
        $this->app->singleton(OrderConversionService::class);
        $this->app->singleton(CommunicationService::class);
        $this->app->singleton(ProductDiscountService::class);
    }

    public function boot(): void
    {
        Gate::policy(Order::class, OrderPolicy::class);

        // Automatically snapshot master pricing whenever it changes.
        Product::observe(ProductPriceObserver::class);

        // Books sync lives here so Sales, Purchasing, and Inventory controllers stay unchanged.
        $booksSync = BooksSyncObserver::class;
        Product::observe($booksSync);
        Customer::observe($booksSync);
        Uom::observe($booksSync);
        \App\Domains\Organization\Models\Warehouse::observe($booksSync);
        Invoice::observe($booksSync);
        Payment::observe($booksSync);
        PurchaseInvoice::observe($booksSync);
        PurchaseOrder::observe($booksSync);
        CreditNote::observe($booksSync);
        \Tally\Models\Product::observe($booksSync);
        \Tally\Models\Unit::observe($booksSync);
        \Tally\Models\Party::observe($booksSync);
        \Tally\Models\Godown::observe($booksSync);
        \Tally\Models\Invoice::observe($booksSync);
        \Tally\Models\Voucher::observe($booksSync);
        \Tally\Models\PurchaseOrder::observe($booksSync);
        Order::observe($booksSync);
        StockAdjustment::observe($booksSync);
        OdAccount::observe($booksSync);
        BankAccountTransaction::observe($booksSync);
        \Tally\Models\SalesOrder::observe($booksSync);
        \Tally\Models\StockTransaction::observe($booksSync);
        \Tally\Models\BankAccount::observe($booksSync);
    }
}

