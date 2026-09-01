<?php

use App\Http\Controllers\Payment\PaymentLinkController;
use Illuminate\Support\Facades\Route;

Route::prefix('pay')->name('payment-links.')->group(function () {
    Route::get('/{token}', [PaymentLinkController::class, 'pay'])->name('pay');
    Route::post('/{token}/complete', [PaymentLinkController::class, 'markPaid'])->name('complete');
});

Route::post('/webhooks/payment', [PaymentLinkController::class, 'webhook'])->name('payment-links.webhook');
