<?php

use App\Http\Controllers\Tally\TallySyncQueueController;
use Illuminate\Support\Facades\Route;

// The TallyPrime office connector is dormant (not required for day-to-day Books).
// Native Books (`routes/modules/books.php`) is the live accounting path.
//
// Future re-enable (kept ready):
// 1. TALLY_SYNC_ENABLED=true
// 2. TALLY_CONNECTOR_TOKEN + TALLY_COMPANY
// 3. Admin UI: reports.tally-connector (DMS sidebar section 11)
// 4. Public API auto-loads from routes/web.php when enabled
// 5. TallySyncServiceProvider registers TallyAutoEnqueueObserver when enabled
//
// Templates: App\Domains\TallySync\Templates
// Config: config/tally_sync.php

Route::prefix('tally/queue')->name('tally.queue.')->middleware('role_or_permission:super-admin|tally.view|tally.manage|masters.manage')->group(function () {
    Route::get('/', [TallySyncQueueController::class, 'index'])->name('index');
    Route::get('/{tally_sync_queue}', [TallySyncQueueController::class, 'show'])->name('show');
    Route::post('/{tally_sync_queue}/retry', [TallySyncQueueController::class, 'retry'])->name('retry');
});
