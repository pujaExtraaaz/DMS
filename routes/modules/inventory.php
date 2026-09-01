<?php

use App\Http\Controllers\Inventory\PurchaseController;
use App\Http\Controllers\Inventory\StockController;
use Illuminate\Support\Facades\Route;

Route::prefix('inventory')->name('inventory.')->middleware('role_or_permission:super-admin|inventory.view|inventory.create|inventory.edit|inventory.manage|stock.view|stock.create|stock.edit|purchases.view|purchases.create|purchases.edit')->group(function () {
    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show']);
});
