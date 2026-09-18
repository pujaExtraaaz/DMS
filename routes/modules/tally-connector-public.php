<?php

use App\Http\Controllers\Tally\TallyConnectorController;
use App\Http\Middleware\VerifyTallyConnectorToken;
use Illuminate\Support\Facades\Route;

Route::prefix('api/tally-connector')->middleware(VerifyTallyConnectorToken::class)->group(function () {
    Route::get('health', [TallyConnectorController::class, 'health']);
    Route::get('pending', [TallyConnectorController::class, 'pending']);
    Route::post('{tally_sync_queue}/result', [TallyConnectorController::class, 'result']);
});
