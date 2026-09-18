<?php

use App\Http\Controllers\Purchasing\FreightBillController;
use App\Http\Controllers\Purchasing\LandedCostController;
use App\Http\Controllers\Purchasing\PurchaseInvoiceController;
use App\Http\Controllers\Purchasing\PurchaseInwardController;
use App\Http\Controllers\Purchasing\PurchaseOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('purchasing')->name('purchasing.')->middleware('role_or_permission:super-admin|purchases.view|purchases.create|purchases.edit|purchases.manage|purchase-orders.view|purchase-orders.create|purchase-orders.edit|purchase-orders.manage|inventory.view|inventory.manage')->group(function () {
    Route::get('orders', [PurchaseOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/create', [PurchaseOrderController::class, 'create'])->name('orders.create');
    Route::post('orders', [PurchaseOrderController::class, 'store'])->name('orders.store');
    Route::get('orders/{order}', [PurchaseOrderController::class, 'show'])->name('orders.show');
    Route::post('orders/{order}/submit', [PurchaseOrderController::class, 'submit'])->name('orders.submit');
    Route::post('orders/{order}/approve', [PurchaseOrderController::class, 'approve'])->name('orders.approve');
    Route::post('orders/{order}/cancel', [PurchaseOrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('orders/{order}/receive', [PurchaseOrderController::class, 'receiveForm'])->name('orders.receive');
    Route::post('orders/{order}/receive', [PurchaseOrderController::class, 'receive'])->name('orders.receive.store');

    Route::get('inwards', [PurchaseInwardController::class, 'index'])->name('inwards.index');
    Route::get('inwards/{inward}', [PurchaseInwardController::class, 'show'])->name('inwards.show');

    Route::get('invoices', [PurchaseInvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/create', [PurchaseInvoiceController::class, 'create'])->name('invoices.create');
    Route::post('invoices', [PurchaseInvoiceController::class, 'store'])->name('invoices.store');

    Route::get('invoices/{invoice}/preview', [PurchaseInvoiceController::class, 'preview'])
        ->name('invoices.preview');
    
    Route::get('invoices/purchase-order/{order}/data', [PurchaseInvoiceController::class, 'purchaseOrderData'])
        ->name('invoices.purchase-order-data');

    Route::get('invoices/{invoice}', [PurchaseInvoiceController::class, 'show'])
        ->name('invoices.show');

    Route::get('landed-costs', [LandedCostController::class, 'index'])->name('landed-costs.index');
    Route::get('landed-costs/create', [LandedCostController::class, 'create'])->name('landed-costs.create');
    Route::post('landed-costs', [LandedCostController::class, 'store'])->name('landed-costs.store');
    Route::get('landed-costs/{landedCost}', [LandedCostController::class, 'show'])->name('landed-costs.show');

    Route::get('freight-bills', [FreightBillController::class, 'index'])->name('freight-bills.index');
    Route::get('freight-bills/create', [FreightBillController::class, 'create'])->name('freight-bills.create');
    Route::post('freight-bills', [FreightBillController::class, 'store'])->name('freight-bills.store');
    Route::get('freight-bills/{freightBill}', [FreightBillController::class, 'show'])->name('freight-bills.show');
});
