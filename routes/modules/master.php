<?php

use App\Http\Controllers\Master\AreaController;
use App\Http\Controllers\Master\CustomerController;
use App\Http\Controllers\Master\CustomerTypeController;
use App\Http\Controllers\Master\DeliveryPersonController;
use App\Http\Controllers\Master\DriverController;
use App\Http\Controllers\Master\PriceMasterController;
use App\Http\Controllers\Master\ProductController;
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
    });
});
