<?php

namespace App\Providers;

use App\Domains\Communication\Services\CommunicationService;
use App\Domains\Inventory\Services\StockMovementService;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PriceMaster;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Master\Observers\ProductPriceObserver;
use App\Domains\Master\Services\PriceMasterService;
use App\Domains\Master\Services\ProductDiscountService;
use App\Domains\Banking\Models\BankAccountTransaction;
use App\Domains\Banking\Models\OdAccount;
use App\Domains\Catalog\Models\Brand;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\SubCategory;
use App\Domains\Crm\Models\Lead;
use App\Domains\Deal\Models\ExpenseType;
use App\Domains\Delivery\Models\Delivery;
use App\Domains\Hrms\Models\Employee;
use App\Domains\Inventory\Models\ProductSerial;
use App\Domains\Inventory\Models\StockAdjustment;
use App\Domains\Inventory\Models\StockLevel;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Inventory\Models\StockTransfer;
use App\Domains\Inventory\Models\StockValuationSetting;
use App\Domains\Logistics\Models\LoadSheet;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Services\OrderConversionService;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\BusinessGroup;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Domains\Payment\Models\Cheque;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Payment\Models\OutstandingLedger;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Services\OutstandingLedgerService;
use App\Domains\Purchasing\Models\FreightBill;
use App\Domains\Purchasing\Models\LandedCost;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\PurchaseInward;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Payment\Services\PaymentLinkService;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\Quotation;
use App\Domains\Sales\Services\InvoiceNumberGenerator;
use App\Domains\Settlement\Models\Settlement;
use App\Domains\Sync\Observers\BooksSyncObserver;
use App\Policies\OrderPolicy;
use App\Support\BooksWorkspace;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
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
        View::composer(['layouts.dms', 'tally::components.layouts.app', 'partials.sidebar', 'partials.books-aside'], function ($view) {
            $view->with('showBooksAside', BooksWorkspace::showAside());
            $view->with('booksWorkspaceActive', BooksWorkspace::active());
        });

        Gate::policy(Order::class, OrderPolicy::class);

        // Books + Spatie: Super Admin / Client Admin bypass every ability check.
        Gate::before(function ($user, string $ability) {
            if (function_exists('tally_super_admin') && tally_super_admin($user)) {
                return true;
            }

            return null;
        });

        // Automatically snapshot master pricing whenever it changes.
        Product::observe(ProductPriceObserver::class);

        // Books sync lives here so Sales, Purchasing, and Inventory controllers stay unchanged.
        $booksSync = BooksSyncObserver::class;
        Company::observe($booksSync);
        Branch::observe($booksSync);
        FinancialYear::observe($booksSync);
        BusinessGroup::observe($booksSync);
        Brand::observe($booksSync);
        Category::observe($booksSync);
        SubCategory::observe($booksSync);
        Product::observe($booksSync);
        Customer::observe($booksSync);
        CustomerType::observe($booksSync);
        PriceMaster::observe($booksSync);
        Uom::observe($booksSync);
        \App\Domains\Organization\Models\Warehouse::observe($booksSync);
        Invoice::observe($booksSync);
        Quotation::observe($booksSync);
        Payment::observe($booksSync);
        OutstandingLedger::observe($booksSync);
        PurchaseInvoice::observe($booksSync);
        PurchaseOrder::observe($booksSync);
        PurchaseInward::observe($booksSync);
        FreightBill::observe($booksSync);
        LandedCost::observe($booksSync);
        CreditNote::observe($booksSync);
        Cheque::observe($booksSync);
        Order::observe($booksSync);
        StockAdjustment::observe($booksSync);
        StockLevel::observe($booksSync);
        StockMovement::observe($booksSync);
        StockTransfer::observe($booksSync);
        ProductSerial::observe($booksSync);
        StockValuationSetting::observe($booksSync);
        LoadSheet::observe($booksSync);
        Delivery::observe($booksSync);
        Settlement::observe($booksSync);
        Employee::observe($booksSync);
        ExpenseType::observe($booksSync);
        Lead::observe($booksSync);
        OdAccount::observe($booksSync);
        BankAccountTransaction::observe($booksSync);
        \Tally\Models\Product::observe($booksSync);
        \Tally\Models\Unit::observe($booksSync);
        \Tally\Models\Party::observe($booksSync);
        \Tally\Models\Godown::observe($booksSync);
        \Tally\Models\Invoice::observe($booksSync);
        \Tally\Models\Voucher::observe($booksSync);
        \Tally\Models\PurchaseOrder::observe($booksSync);
        \Tally\Models\SalesOrder::observe($booksSync);
        \Tally\Models\StockTransaction::observe($booksSync);
        \Tally\Models\BankAccount::observe($booksSync);
    }
}
