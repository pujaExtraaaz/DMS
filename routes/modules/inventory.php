<?php

use App\Http\Controllers\Inventory\BatchLookupController;
use App\Http\Controllers\Inventory\PurchaseController;
use App\Http\Controllers\Inventory\SerialLookupController;
use App\Http\Controllers\Inventory\StockAdjustmentController;
use App\Http\Controllers\Inventory\StockController;
use App\Http\Controllers\Inventory\StockTransferController;
use App\Http\Controllers\Inventory\StockValuationSettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('inventory')->name('inventory.')->middleware('role_or_permission:super-admin|inventory.view|inventory.create|inventory.edit|inventory.manage|stock.view|stock.create|stock.edit|purchases.view|purchases.create|purchases.edit')->group(function () {
    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show']);

    Route::get('transfers', [StockTransferController::class, 'index'])->name('transfers.index');
    Route::get('transfers/create', [StockTransferController::class, 'create'])->name('transfers.create');
    Route::get('transfers/stock-availability', [StockTransferController::class, 'stockAvailability'])->name('transfers.stock-availability');
    Route::post('transfers/name-transfer', [StockTransferController::class, 'storeNameTransfer'])->name('transfers.name-transfer');
    Route::get('transfers/reclassifications/{reclassification}', [StockTransferController::class, 'showReclassification'])->name('transfers.reclassifications.show');
    Route::get('transfers/reclassifications/{reclassification}/edit', [StockTransferController::class, 'editReclassification'])->name('transfers.reclassifications.edit');
    Route::put('transfers/reclassifications/{reclassification}', [StockTransferController::class, 'updateReclassification'])->name('transfers.reclassifications.update');
    Route::delete('transfers/reclassifications/{reclassification}', [StockTransferController::class, 'destroyReclassification'])->name('transfers.reclassifications.destroy');
    Route::post('transfers', [StockTransferController::class, 'store'])->name('transfers.store');
    Route::get('transfers/{transfer}', [StockTransferController::class, 'show'])->name('transfers.show');
    Route::get('transfers/{transfer}/edit', [StockTransferController::class, 'edit'])->name('transfers.edit');
    Route::put('transfers/{transfer}', [StockTransferController::class, 'update'])->name('transfers.update');
    Route::delete('transfers/{transfer}', [StockTransferController::class, 'destroy'])->name('transfers.destroy');
    Route::post('transfers/{transfer}/dispatch', [StockTransferController::class, 'dispatch'])->name('transfers.dispatch');
    Route::post('transfers/{transfer}/receive', [StockTransferController::class, 'receive'])->name('transfers.receive');

    Route::get('adjustments', [StockAdjustmentController::class, 'index'])->name('adjustments.index');
    Route::get('adjustments/create', [StockAdjustmentController::class, 'create'])->name('adjustments.create');
    Route::post('adjustments', [StockAdjustmentController::class, 'store'])->name('adjustments.store');
    Route::get('adjustments/{adjustment}', [StockAdjustmentController::class, 'show'])->name('adjustments.show');
    Route::get('adjustments/{adjustment}/edit', [StockAdjustmentController::class, 'edit'])->name('adjustments.edit');
    Route::put('adjustments/{adjustment}', [StockAdjustmentController::class, 'update'])->name('adjustments.update');
    Route::delete('adjustments/{adjustment}', [StockAdjustmentController::class, 'destroy'])->name('adjustments.destroy');

    Route::get('batches/{product}', BatchLookupController::class)->name('batches.lookup');

    Route::get('serials', [SerialLookupController::class, 'index'])->name('serials.index');
    Route::post('serials/reserve', [SerialLookupController::class, 'reserve'])->name('serials.reserve');
    Route::post('serials/deliver', [SerialLookupController::class, 'deliver'])->name('serials.deliver');
    Route::post('serials/return', [SerialLookupController::class, 'returnSerials'])->name('serials.return');

    Route::get('valuation', [StockValuationSettingController::class, 'index'])->name('valuation.index');
    Route::post('valuation', [StockValuationSettingController::class, 'store'])->name('valuation.store');
});
