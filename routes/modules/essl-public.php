<?php

use App\Http\Controllers\Hrms\EsslConnectorController;
use App\Http\Middleware\VerifyEsslConnectorToken;
use Illuminate\Support\Facades\Route;

Route::prefix('api/essl')->middleware(VerifyEsslConnectorToken::class)->group(function () {
    Route::get('health', [EsslConnectorController::class, 'health']);
    Route::post('punches', [EsslConnectorController::class, 'punches']);
});
