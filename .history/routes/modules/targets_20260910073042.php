<?php

use App\Http\Controllers\Target\TargetController;
use Illuminate\Support\Facades\Route;

Route::prefix('targets')->name('targets.')->middleware('role_or_permission:super-admin|targets.view|targets.create|targets.edit|targets.manage')->group(function () {
    Route::get('/', [TargetController::class, 'index'])->name('index');
    Route::get('/periods/create', [TargetController::class, 'createPeriod'])->name('periods.create');
    Route::post('/periods', [TargetController::class, 'storePeriod'])->name('periods.store');
    Route::get('/periods/{target_period}', [TargetController::class, 'showPeriod'])->name('periods.show');
    Route::post('/periods/{target_period}/recalculate', [TargetController::class, 'recalculate'])->name('periods.recalculate');
    Route::post('/periods/{target_period}/close', [TargetController::class, 'close'])->name('periods.close');
    Route::get('/periods/{target_period}/targets/create', [TargetController::class, 'createTarget'])->name('create');
    Route::post('/periods/{target_period}/targets', [TargetController::class, 'storeTarget'])->name('store');
});
