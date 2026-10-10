<?php

use App\Http\Controllers\Crm\LeadBulkUploadController;
use App\Http\Controllers\Crm\LeadController;
use Illuminate\Support\Facades\Route;

Route::prefix('crm')->name('crm.')->middleware('role_or_permission:super-admin|crm.view|crm.create|crm.edit|crm.manage')->group(function () {
    Route::get('leads', [LeadController::class, 'index'])->name('leads.index');
    Route::get('leads/create', [LeadController::class, 'create'])->name('leads.create');
    Route::post('leads', [LeadController::class, 'store'])->name('leads.store');

    // Bulk upload routes (must precede {lead} wildcard)
    Route::get('leads/bulk-upload', [LeadBulkUploadController::class, 'index'])->name('leads.bulk-upload');
    Route::get('leads/bulk-upload/template', [LeadBulkUploadController::class, 'template'])->name('leads.bulk-upload.template');
    Route::post('leads/bulk-upload/preview', [LeadBulkUploadController::class, 'preview'])->name('leads.bulk-upload.preview');
    Route::post('leads/bulk-upload/import', [LeadBulkUploadController::class, 'import'])->name('leads.bulk-upload.import');
    Route::get('leads/bulk-upload/errors/{filename}', [LeadBulkUploadController::class, 'downloadErrors'])->name('leads.bulk-upload.errors');
    Route::post('leads/check-duplicate', [LeadController::class, 'checkDuplicate'])->name('leads.check-duplicate');
    Route::get('leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
    Route::get('leads/{lead}/edit', [LeadController::class, 'edit'])->name('leads.edit');
    Route::put('leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
    Route::delete('leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');
    Route::match(['patch', 'post'], 'leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.update-status');
    Route::post('leads/{lead}/assign', [LeadController::class, 'assign'])->name('leads.assign');
    Route::post('leads/{lead}/followup', [LeadController::class, 'followup'])->name('leads.followup');
    Route::post('leads/{lead}/convert', [LeadController::class, 'convert'])->name('leads.convert');
});
