<?php

use App\Http\Controllers\Communication\CommunicationController;
use App\Http\Controllers\Sales\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('communications')->name('communications.')->middleware('role_or_permission:super-admin|communications.view|communications.create|communications.edit|communications.manage')->group(function () {
    Route::get('/', [CommunicationController::class, 'index'])->name('index');
    Route::post('/invoice/{invoice}/whatsapp', [CommunicationController::class, 'sendInvoice'])->name('send-invoice');
    Route::post('/invoice/{invoice}/payment-link', [CommunicationController::class, 'sendPaymentLink'])->name('send-payment-link');
    Route::post('/invoice/{invoice}/reminder', [CommunicationController::class, 'sendReminder'])->name('send-reminder');
});
