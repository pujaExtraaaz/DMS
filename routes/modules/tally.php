<?php

use App\Http\Controllers\Tally\TallySyncQueueController;
use Illuminate\Support\Facades\Route;

Route::prefix('tally')->name('tally.')->middleware('role_or_permission:super-admin|tally.view|tally.create|tally.edit|tally.manage')->group(function () {
    Route::get('queue', [TallySyncQueueController::class, 'index'])->name('queue.index');
    Route::get('queue/{tally_sync_queue}', [TallySyncQueueController::class, 'show'])->name('queue.show');
    Route::post('queue/{tally_sync_queue}/retry', [TallySyncQueueController::class, 'retry'])->name('queue.retry');
});
