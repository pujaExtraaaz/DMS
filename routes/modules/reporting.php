<?php

use App\Http\Controllers\Reporting\DashboardController;
use App\Http\Controllers\Reporting\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::prefix('reports')->name('reports.')->middleware('role:owner|super-admin|sales-manager|finance')->group(function () {
    Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
    Route::get('/stock', [ReportController::class, 'stock'])->name('stock');
    Route::get('/payments', [ReportController::class, 'payments'])->name('payments');
    Route::get('/outstanding', [ReportController::class, 'outstanding'])->name('outstanding');
    Route::get('/delivery', [ReportController::class, 'delivery'])->name('delivery');
});
