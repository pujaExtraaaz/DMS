<?php

use App\Http\Controllers\Target\SalesTargetController;
use Illuminate\Support\Facades\Route;

Route::prefix('sales-targets')->name('sales-targets.')->group(function () {
    Route::get('/dashboard', [SalesTargetController::class, 'dashboard'])->name('dashboard');
    Route::get('/', [SalesTargetController::class, 'index'])->name('index');
    Route::get('/create', [SalesTargetController::class, 'create'])->name('create');
    Route::post('/', [SalesTargetController::class, 'store'])->name('store');
    Route::get('/salesman-performance', [SalesTargetController::class, 'salesmanPerformance'])->name('salesman-performance');
    Route::get('/team-branch-performance', [SalesTargetController::class, 'teamBranchPerformance'])->name('team-branch-performance');
    Route::get('/alerts', [SalesTargetController::class, 'alerts'])->name('alerts');
    Route::post('/alerts/{alert}/read', [SalesTargetController::class, 'markAlertRead'])->name('alerts.read');
    Route::post('/alerts/read-all', [SalesTargetController::class, 'markAllAlertsRead'])->name('alerts.read-all');
    Route::get('/reports', [SalesTargetController::class, 'reports'])->name('reports');
    Route::post('/recalculate', [SalesTargetController::class, 'recalculate'])->name('recalculate');

    Route::get('/{salesTarget}', [SalesTargetController::class, 'show'])->name('show');
    Route::get('/{salesTarget}/edit', [SalesTargetController::class, 'edit'])->name('edit');
    Route::put('/{salesTarget}', [SalesTargetController::class, 'update'])->name('update');
    Route::post('/{salesTarget}/approve', [SalesTargetController::class, 'approve'])->name('approve');
    Route::post('/{salesTarget}/cancel', [SalesTargetController::class, 'cancel'])->name('cancel');
    Route::post('/{salesTarget}/carry-forward', [SalesTargetController::class, 'carryForward'])->name('carry-forward');
});

