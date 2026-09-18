<?php

use App\Http\Controllers\Master\AreaController;
use App\Http\Controllers\Master\BulkImportController;
use App\Http\Controllers\Master\CustomerController;
use App\Http\Controllers\Master\CustomerTypeController;
use App\Http\Controllers\Master\DeliveryPersonController;
use App\Http\Controllers\Master\DriverController;
use App\Http\Controllers\Master\PriceMasterController;
use App\Http\Controllers\Master\ProductController;
use App\Http\Controllers\Master\QuickAddProductController;
use App\Http\Controllers\Master\RouteController;
use App\Http\Controllers\Master\UomController;
use App\Http\Controllers\Master\VehicleController;
use Illuminate\Support\Facades\Route;

Route::prefix('masters')->name('masters.')->middleware('role_or_permission:super-admin|masters.view|masters.create|masters.edit|masters.manage|products.view|products.create|products.edit|customers.view|customers.create|customers.edit|price-master.view|price-master.create|price-master.edit')->group(function () {
    Route::resource('customer-types', CustomerTypeController::class);
    Route::resource('areas', AreaController::class);
    Route::resource('routes', RouteController::class);
    Route::resource('uoms', UomController::class);
    Route::resource('vehicles', VehicleController::class);
    Route::resource('drivers', DriverController::class);
    Route::resource('delivery-persons', DeliveryPersonController::class);

    Route::middleware('role_or_permission:super-admin|masters.view|masters.create|masters.edit|masters.manage|products.view|products.create|products.edit|customers.view|customers.create|customers.edit|price-master.view|price-master.create|price-master.edit')->group(function () {
        Route::resource('products', ProductController::class);
        Route::resource('price-masters', PriceMasterController::class);
        Route::resource('customers', CustomerController::class);

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
