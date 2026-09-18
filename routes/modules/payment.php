<?php

use App\Http\Controllers\Payment\ChequeController;
use App\Http\Controllers\Payment\CreditNoteController;
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

Route::prefix('cheques')->name('cheques.')->middleware('role_or_permission:super-admin|cheques.view|cheques.create|cheques.edit|cheques.manage|payments.view|payments.manage')->group(function () {
    Route::get('/', [ChequeController::class, 'index'])->name('index');
    Route::get('/create', [ChequeController::class, 'create'])->name('create');
    Route::post('/', [ChequeController::class, 'store'])->name('store');
    Route::get('/{cheque}', [ChequeController::class, 'show'])->name('show');
    Route::post('/{cheque}/deposit', [ChequeController::class, 'deposit'])->name('deposit');
    Route::post('/{cheque}/clear', [ChequeController::class, 'clear'])->name('clear');
    Route::post('/{cheque}/bounce', [ChequeController::class, 'bounce'])->name('bounce');
    Route::post('/{cheque}/cancel', [ChequeController::class, 'cancel'])->name('cancel');
});

Route::prefix('credit-notes')->name('credit-notes.')->middleware('role_or_permission:super-admin|credit-notes.view|credit-notes.create|credit-notes.edit|credit-notes.manage|payments.view|payments.manage')->group(function () {
    Route::get('/', [CreditNoteController::class, 'index'])->name('index');
    Route::get('/create', [CreditNoteController::class, 'create'])->name('create');
    Route::post('/', [CreditNoteController::class, 'store'])->name('store');
    Route::get('/{credit_note}', [CreditNoteController::class, 'show'])->name('show');
    Route::post('/{credit_note}/approve', [CreditNoteController::class, 'approve'])->name('approve');
    Route::post('/{credit_note}/post', [CreditNoteController::class, 'post'])->name('post');
});

Route::post('/invoices/{invoice}/payment-link', [PaymentLinkController::class, 'create'])
    ->middleware('role_or_permission:super-admin|payments.create|payments.edit|payments.manage')
    ->name('payment-links.create');
