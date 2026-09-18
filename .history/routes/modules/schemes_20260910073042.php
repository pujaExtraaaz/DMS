<?php

use App\Http\Controllers\Scheme\SchemeController;
use Illuminate\Support\Facades\Route;

Route::prefix('schemes')->name('schemes.')->middleware('role_or_permission:super-admin|schemes.view|schemes.create|schemes.edit|schemes.manage')->group(function () {
    Route::get('/', [SchemeController::class, 'index'])->name('index');
    Route::get('/create', [SchemeController::class, 'create'])->name('create');
    Route::post('/', [SchemeController::class, 'store'])->name('store');
    Route::get('/{scheme}', [SchemeController::class, 'show'])->name('show');
    Route::post('/{scheme}/activate', [SchemeController::class, 'activate'])->name('activate');
    Route::post('/{scheme}/finalize', [SchemeController::class, 'finalize'])->name('finalize');
});
