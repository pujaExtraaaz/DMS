<?php

namespace App\Providers;

use App\Domains\Communication\Services\CommunicationService;
use App\Domains\Inventory\Services\StockMovementService;
use App\Domains\Master\Services\PriceMasterService;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Services\OrderConversionService;
use App\Domains\Payment\Services\OutstandingLedgerService;
use App\Domains\Payment\Services\PaymentLinkService;
use App\Domains\Sales\Services\InvoiceNumberGenerator;
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
    }

    public function boot(): void
    {
        Gate::policy(Order::class, OrderPolicy::class);
    }
}