<?php

use App\Http\Controllers\Settlement\SettlementController;
use Illuminate\Support\Facades\Route;

Route::prefix('settlements')->name('settlements.')->middleware('role_or_permission:super-admin|settlement.view|settlement.create|settlement.edit|settlement.manage|manage settlements|settlement.entry')->group(function () {
    Route::get('/', [SettlementController::class, 'index'])->name('index');
    Route::get('/create/{load_sheet}', [SettlementController::class, 'create'])->name('create');
    Route::post('/{load_sheet}', [SettlementController::class, 'store'])->name('store');
    Route::get('/show/{settlement}', [SettlementController::class, 'show'])->name('show');
});
