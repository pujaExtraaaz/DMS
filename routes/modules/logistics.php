<?php

use App\Http\Controllers\Logistics\LoadSheetController;
use Illuminate\Support\Facades\Route;

Route::prefix('logistics/load-sheets')->name('logistics.load-sheets.')->middleware('role_or_permission:super-admin|logistics.view|logistics.create|logistics.edit|logistics.manage')->group(function () {
    Route::get('/', [LoadSheetController::class, 'index'])->name('index');
    Route::get('/create', [LoadSheetController::class, 'create'])->name('create');
    Route::post('/', [LoadSheetController::class, 'store'])->name('store');
    Route::get('/{load_sheet}', [LoadSheetController::class, 'show'])->name('show');
    Route::post('/{load_sheet}/dispatch', [LoadSheetController::class, 'dispatch'])->name('dispatch');
});
