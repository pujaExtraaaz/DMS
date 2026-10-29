<?php

use App\Http\Controllers\Master\AreaController;
use App\Http\Controllers\Master\BulkImportController;
use App\Http\Controllers\Master\CustomerController;
use App\Http\Controllers\Master\CustomerTypeController;
use App\Http\Controllers\Master\DeliveryPersonController;
use App\Http\Controllers\Master\DriverController;
use App\Http\Controllers\Master\PriceMasterController;
use App\Http\Controllers\Master\ProductController;
use App\Http\Controllers\Master\ProductTypeController;
use App\Http\Controllers\Master\QuickAddProductController;
use App\Http\Controllers\Master\RouteController;
use App\Http\Controllers\Master\UomController;
use App\Http\Controllers\Master\VehicleController;
use Illuminate\Support\Facades\Route;

Route::prefix('masters')->name('masters.')->middleware('role_or_permission:super-admin|masters.view|masters.create|masters.edit|masters.delete|masters.manage|products.view|products.create|products.edit|customers.view|customers.create|customers.edit|price-master.view|price-master.create|price-master.edit|price-master.delete|orders.create|orders.book|orders.manage|purchases.create|purchase-orders.create|crm.manage|crm.edit')->group(function () {
    Route::post('customer-types/quick-store', [CustomerTypeController::class, 'quickStore'])->name('customer-types.quick-store');
    Route::get('customer-types/list', [CustomerTypeController::class, 'list'])->name('customer-types.list');
    Route::resource('customer-types', CustomerTypeController::class);
    Route::post('product-types/quick-store', [ProductTypeController::class, 'quickStore'])->name('product-types.quick-store');
    Route::get('product-types/list', [ProductTypeController::class, 'list'])->name('product-types.list');
    Route::resource('product-types', ProductTypeController::class);
    Route::resource('areas', AreaController::class);
    Route::resource('routes', RouteController::class);
    Route::resource('uoms', UomController::class);
    Route::resource('vehicles', VehicleController::class);
    Route::resource('drivers', DriverController::class);
    Route::resource('delivery-persons', DeliveryPersonController::class);

    Route::middleware('role_or_permission:super-admin|masters.view|masters.create|masters.edit|masters.delete|masters.manage|products.view|products.create|products.edit|customers.view|customers.create|customers.edit|price-master.view|price-master.create|price-master.edit|price-master.delete|orders.create|orders.book|orders.manage|purchases.create|purchase-orders.create|crm.manage|crm.edit')->group(function () {
        Route::patch('products/{product}/assign-type', [ProductController::class, 'assignType'])->name('products.assign-type');
        Route::resource('products', ProductController::class);
        Route::get('price-masters/copy-options', [PriceMasterController::class, 'copyOptions'])->name('price-masters.copy-options');
        Route::get('price-masters/copy-source', [PriceMasterController::class, 'copySource'])->name('price-masters.copy-source');
        Route::get('price-masters/brands/{brand}/products', [PriceMasterController::class, 'brandProducts'])->name('price-masters.brand-products');
        Route::get('price-masters/products/{product}/details', [PriceMasterController::class, 'productDetails'])->name('price-masters.product-details');
        Route::resource('price-masters', PriceMasterController::class);
        Route::delete('price-lists/{price_master}', [PriceMasterController::class, 'destroy'])->name('price-lists.destroy');

        // GST / IFSC lookup & quick-add endpoints for Party/Customer
        Route::match(['get', 'post'], 'customers/gst-lookup', [CustomerController::class, 'gstLookup'])->name('customers.gst-lookup');
        Route::match(['get', 'post'], 'customers/ifsc-lookup', [CustomerController::class, 'ifscLookup'])->name('customers.ifsc-lookup');
        Route::post('customers/quick-add', [CustomerController::class, 'quickAdd'])->name('customers.quick-add');
        Route::get('customers/business-groups', [CustomerController::class, 'businessGroups'])->name('customers.business-groups');
        Route::get('customers/{customer}/addresses', [CustomerController::class, 'addresses'])->name('customers.addresses');
        Route::get('cities', [CustomerController::class, 'cities'])->name('cities');
        Route::resource('customers', CustomerController::class);
        Route::get('parties', [CustomerController::class, 'index'])->name('parties.index');
        Route::delete('parties/{customer}', [CustomerController::class, 'destroy'])->name('parties.destroy');

        // Quick-add product endpoint used by PO / PI / Sales create flows.
        Route::post('products/quick-add', [QuickAddProductController::class, 'store'])->name('products.quick-add');
        Route::get('products/quick-add/options', [QuickAddProductController::class, 'options'])->name('products.quick-add.options');

        // Bulk import for Products, Parties, and Price Master (AVIT req #22).
        Route::get('bulk-import', [BulkImportController::class, 'index'])->name('bulk-import.index');
        Route::get('bulk-import/template/{type}', [BulkImportController::class, 'template'])->name('bulk-import.template');
        Route::post('bulk-import/{type}', [BulkImportController::class, 'upload'])->name('bulk-import.upload');
        Route::get('bulk-import/errors/{filename}', [BulkImportController::class, 'errors'])->name('bulk-import.errors');
    });
});