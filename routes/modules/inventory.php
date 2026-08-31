<?php

use App\Http\Controllers\Inventory\PurchaseController;
use App\Http\Controllers\Inventory\StockController;
use Illuminate\Support\Facades\Route;

Route::prefix('inventory')->name('inventory.')->middleware('role:owner|super-admin|warehouse|sales-manager')->group(function () {
    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show']);
});
