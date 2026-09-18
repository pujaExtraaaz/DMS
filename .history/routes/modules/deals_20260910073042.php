<?php

use App\Http\Controllers\Deal\DealController;
use App\Http\Controllers\Deal\ExpenseTypeController;
use Illuminate\Support\Facades\Route;

Route::prefix('expense-types')->name('expense-types.')->middleware('role_or_permission:super-admin|deals.view|deals.create|deals.edit|deals.manage')->group(function () {
    Route::get('/', [ExpenseTypeController::class, 'index'])->name('index');
    Route::get('/create', [ExpenseTypeController::class, 'create'])->name('create');
    Route::post('/', [ExpenseTypeController::class, 'store'])->name('store');
    Route::get('/{expense_type}/edit', [ExpenseTypeController::class, 'edit'])->name('edit');
    Route::put('/{expense_type}', [ExpenseTypeController::class, 'update'])->name('update');
});

Route::prefix('deals')->name('deals.')->middleware('role_or_permission:super-admin|deals.view|deals.create|deals.edit|deals.manage')->group(function () {
    Route::get('/', [DealController::class, 'index'])->name('index');
    Route::get('/create', [DealController::class, 'create'])->name('create');
    Route::post('/', [DealController::class, 'store'])->name('store');
    Route::get('/{deal}', [DealController::class, 'show'])->name('show');
    Route::post('/{deal}/expenses', [DealController::class, 'addExpense'])->name('expenses.store');
    Route::post('/{deal}/refresh-margin', [DealController::class, 'refreshMargin'])->name('refresh-margin');
    Route::post('/expenses/{deal_expense}/approve', [DealController::class, 'approveExpense'])->name('expenses.approve');
    Route::post('/expenses/{deal_expense}/reject', [DealController::class, 'rejectExpense'])->name('expenses.reject');
});
