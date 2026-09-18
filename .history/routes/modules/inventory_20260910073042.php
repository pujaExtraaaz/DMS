<?php

use App\Http\Controllers\Inventory\PurchaseController;
use App\Http\Controllers\Inventory\SerialLookupController;
use App\Http\Controllers\Inventory\StockAdjustmentController;
use App\Http\Controllers\Inventory\StockController;
use App\Http\Controllers\Inventory\StockTransferController;
use Illuminate\Support\Facades\Route;

Route::prefix('inventory')->name('inventory.')->middleware('role_or_permission:super-admin|inventory.view|inventory.create|inventory.edit|inventory.manage|stock.view|stock.create|stock.edit|purchases.view|purchases.create|purchases.edit')->group(function () {
    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show']);

    Route::get('transfers', [StockTransferController::class, 'index'])->name('transfers.index');
    Route::get('transfers/create', [StockTransferController::class, 'create'])->name('transfers.create');
    Route::post('transfers', [StockTransferController::class, 'store'])->name('transfers.store');
    Route::get('transfers/{transfer}', [StockTransferController::class, 'show'])->name('transfers.show');
    Route::post('transfers/{transfer}/dispatch', [StockTransferController::class, 'dispatch'])->name('transfers.dispatch');
    Route::post('transfers/{transfer}/receive', [StockTransferController::class, 'receive'])->name('transfers.receive');

    Route::get('adjustments', [StockAdjustmentController::class, 'index'])->name('adjustments.index');
    Route::get('adjustments/create', [StockAdjustmentController::class, 'create'])->name('adjustments.create');
    Route::post('adjustments', [StockAdjustmentController::class, 'store'])->name('adjustments.store');
    Route::get('adjustments/{adjustment}', [StockAdjustmentController::class, 'show'])->name('adjustments.show');

    Route::get('serials', [SerialLookupController::class, 'index'])->name('serials.index');
});
