<?php

use App\Http\Controllers\Delivery\DeliveryController;
use Illuminate\Support\Facades\Route;

Route::prefix('deliveries')->name('deliveries.')->middleware('role:owner|super-admin|driver|delivery-person|warehouse')->group(function () {
    Route::get('/', [DeliveryController::class, 'index'])->name('index');
    Route::get('/{delivery}', [DeliveryController::class, 'show'])->name('show');
    Route::get('/{delivery}/edit', [DeliveryController::class, 'edit'])->name('edit');
    Route::put('/{delivery}', [DeliveryController::class, 'update'])->name('update');
});
