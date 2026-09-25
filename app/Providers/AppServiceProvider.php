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
use App\Domains\Order\Models\Order;
use App\Domains\Order\Services\OrderConversionService;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Services\OutstandingLedgerService;
use App\Domains\Payment\Services\PaymentLinkService;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Services\InvoiceNumberGenerator;
use App\Domains\Tally\Observers\TallyAutoEnqueueObserver;
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

        // ── Tally sync ──────────────────────────────────────────────────
        // Masters (Product, Customer, UOM) — sync on every save/update.
        Product::observe(TallyAutoEnqueueObserver::class);
        Customer::observe(TallyAutoEnqueueObserver::class);
        Uom::observe(TallyAutoEnqueueObserver::class);

        // Vouchers — sync only when they reach a posted status.
        Invoice::observe(TallyAutoEnqueueObserver::class);
        Payment::observe(TallyAutoEnqueueObserver::class);
        CreditNote::observe(TallyAutoEnqueueObserver::class);
        PurchaseInvoice::observe(TallyAutoEnqueueObserver::class);
        PurchaseOrder::observe(TallyAutoEnqueueObserver::class);
    }
}

