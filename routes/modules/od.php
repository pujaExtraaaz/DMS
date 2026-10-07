<?php

use App\Http\Controllers\Banking\OdAccountController;
use App\Http\Controllers\Banking\OdReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    // OD Limit & Interest Calculation module
    Route::get('/od', [OdAccountController::class, 'index'])->name('od.index');
    Route::post('/od', [OdAccountController::class, 'store'])->name('od.store');
    Route::get('/od/bank-details', [OdAccountController::class, 'bankDetails'])->name('od.bank-details');

    // Bank account transactions for OD
    Route::post('/od/transactions', [OdAccountController::class, 'storeTransaction'])->name('od.transactions.store');
    Route::delete('/od/transactions/{transaction}', [OdAccountController::class, 'destroyTransaction'])->name('od.transactions.destroy');

    // OD Report
    Route::get('/od/report', [OdReportController::class, 'index'])->name('od.report');
    Route::get('/reports/od', [OdReportController::class, 'index'])->name('reports.od');
});

