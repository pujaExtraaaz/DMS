<?php

use App\Http\Controllers\Interest\InterestController;
use Illuminate\Support\Facades\Route;

Route::prefix('interest')->name('interest.')->middleware('role_or_permission:super-admin|interest.view|interest.create|interest.edit|interest.manage')->group(function () {
    Route::get('/', [InterestController::class, 'index'])->name('index');
    Route::get('/rules', [InterestController::class, 'rules'])->name('rules.index');
    Route::get('/rules/create', [InterestController::class, 'createRule'])->name('rules.create');
    Route::post('/rules', [InterestController::class, 'storeRule'])->name('rules.store');
    Route::get('/rules/{interest_rule}/edit', [InterestController::class, 'editRule'])->name('rules.edit');
    Route::put('/rules/{interest_rule}', [InterestController::class, 'updateRule'])->name('rules.update');
    Route::post('/preview', [InterestController::class, 'preview'])->name('preview');
    Route::post('/ledgers/{interest_ledger}/post', [InterestController::class, 'post'])->name('ledgers.post');
    Route::get('/documents', [InterestController::class, 'documents'])->name('documents.index');
});
