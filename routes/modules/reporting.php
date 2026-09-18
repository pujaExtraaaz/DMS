<?php

use App\Http\Controllers\Reporting\DashboardController;
use App\Http\Controllers\Reporting\FinancialReportController;
use App\Http\Controllers\Reporting\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::prefix('reports')->name('reports.')->middleware('role_or_permission:super-admin|reports.view|reports.create|reports.edit|reports.manage')->group(function () {
    Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
    Route::get('/stock', [ReportController::class, 'stock'])->name('stock');
    Route::get('/payments', [ReportController::class, 'payments'])->name('payments');
    Route::get('/outstanding', [ReportController::class, 'outstanding'])->name('outstanding');
    Route::get('/delivery', [ReportController::class, 'delivery'])->name('delivery');
    Route::get('/pending-orders', [ReportController::class, 'pendingOrders'])->name('pending-orders');
    Route::get('/party-statement', [ReportController::class, 'partyStatement'])->name('party-statement');
    Route::get('/salesman-outstanding', [ReportController::class, 'salesmanOutstanding'])->name('salesman-outstanding');
    Route::get('/stock-ledger', [ReportController::class, 'stockLedger'])->name('stock-ledger');
    Route::get('/purchase-register', [ReportController::class, 'purchaseRegister'])->name('purchase-register');
    Route::get('/margin', [ReportController::class, 'margin'])->name('margin');
    Route::get('/aging', [ReportController::class, 'aging'])->name('aging');

    // Finance reports (Day Book, P&L, BS, Trial Balance) — CSV/PDF export supported.
    Route::get('/day-book', [FinancialReportController::class, 'dayBook'])->name('day-book');
    Route::get('/profit-loss', [FinancialReportController::class, 'profitLoss'])->name('profit-loss');
    Route::get('/balance-sheet', [FinancialReportController::class, 'balanceSheet'])->name('balance-sheet');
    Route::get('/trial-balance', [FinancialReportController::class, 'trialBalance'])->name('trial-balance');
});
