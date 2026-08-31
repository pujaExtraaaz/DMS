<?php

use App\Http\Controllers\Order\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('orders')->name('orders.')->middleware('role:owner|super-admin|sales-manager|salesperson')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/create', [OrderController::class, 'create'])->name('create');
    Route::post('/', [OrderController::class, 'store'])->name('store');
    Route::get('/resolve-price', [OrderController::class, 'resolvePrice'])->name('resolve-price');
    Route::get('/{order}', [OrderController::class, 'show'])->name('show');
    Route::post('/{order}/approve', [OrderController::class, 'approve'])->name('approve');
    Route::post('/{order}/convert', [OrderController::class, 'convert'])->name('convert');
});
