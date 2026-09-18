<?php

use App\Http\Controllers\Crm\LeadController;
use Illuminate\Support\Facades\Route;

Route::prefix('crm')->name('crm.')->middleware('role_or_permission:super-admin|crm.view|crm.create|crm.edit|crm.manage')->group(function () {
    Route::get('leads', [LeadController::class, 'index'])->name('leads.index');
    Route::get('leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
    Route::post('leads/{lead}/assign', [LeadController::class, 'assign'])->name('leads.assign');
    Route::post('leads/{lead}/followup', [LeadController::class, 'followup'])->name('leads.followup');
    Route::post('leads/{lead}/convert', [LeadController::class, 'convert'])->name('leads.convert');
});
