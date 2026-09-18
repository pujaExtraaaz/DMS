<?php

use App\Http\Controllers\Crm\MetaWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('webhooks/meta')->name('meta.webhook.')->group(function () {
    Route::get('/leads', [MetaWebhookController::class, 'verify'])->name('verify');
    Route::post('/leads', [MetaWebhookController::class, 'receive'])->name('receive');
});
