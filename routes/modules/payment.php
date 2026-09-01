<?php

use App\Http\Controllers\Payment\OutstandingController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\Payment\PaymentLinkController;
use App\Http\Controllers\Payment\ReconciliationController;
use Illuminate\Support\Facades\Route;

Route::middleware('role_or_permission:super-admin|payments.view|payments.create|payments.edit|payments.manage|payments.reconcile')->group(function () {
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::get('/create', [PaymentController::class, 'create'])->name('create');
        Route::post('/', [PaymentController::class, 'store'])->name('store');
        Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
    });

    Route::get('/reconciliation', [ReconciliationController::class, 'index'])->name('reconciliation.index');
    Route::get('/outstanding', [OutstandingController::class, 'index'])->name('outstanding.index');
});

Route::post('/invoices/{invoice}/payment-link', [PaymentLinkController::class, 'create'])
    ->middleware('role_or_permission:super-admin|payments.create|payments.edit|payments.manage')
    ->name('payment-links.create');
