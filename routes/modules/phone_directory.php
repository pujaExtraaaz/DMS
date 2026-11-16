<?php

use App\Http\Controllers\PhoneDirectory\PhoneDirectoryController;
use App\Http\Controllers\PhoneDirectory\PhoneDirectoryDepartmentController;
use App\Http\Controllers\PhoneDirectory\PhoneDirectoryExportController;
use App\Http\Controllers\PhoneDirectory\PhoneDirectoryImportController;
use Illuminate\Support\Facades\Route;

Route::prefix('phone-directory')->name('phone-directory.')->group(function () {
    // Listing & Contacts Management
    Route::get('/', [PhoneDirectoryController::class, 'index'])->name('index');
    Route::get('/contacts/create', [PhoneDirectoryController::class, 'create'])->name('contacts.create');
    Route::post('/contacts', [PhoneDirectoryController::class, 'store'])->name('contacts.store');
    Route::get('/contacts/{contact}', [PhoneDirectoryController::class, 'show'])->name('contacts.show');
    Route::get('/contacts/{contact}/edit', [PhoneDirectoryController::class, 'edit'])->name('contacts.edit');
    Route::put('/contacts/{contact}', [PhoneDirectoryController::class, 'update'])->name('contacts.update');
    Route::delete('/contacts/{contact}', [PhoneDirectoryController::class, 'destroy'])->name('contacts.destroy');

    // Quick Actions
    Route::post('/contacts/{contact}/archive', [PhoneDirectoryController::class, 'archive'])->name('contacts.archive');
    Route::post('/contacts/{contact}/toggle-important', [PhoneDirectoryController::class, 'toggleImportant'])->name('contacts.toggle-important');
    Route::post('/contacts/{contact}/toggle-status', [PhoneDirectoryController::class, 'toggleStatus'])->name('contacts.toggle-status');
    Route::post('/contacts/bulk-action', [PhoneDirectoryController::class, 'bulkAction'])->name('contacts.bulk-action');

    // Exports
    Route::get('/export/csv', [PhoneDirectoryExportController::class, 'exportCsv'])->name('export.csv');
    Route::get('/export/excel', [PhoneDirectoryExportController::class, 'exportExcel'])->name('export.excel');
    Route::get('/export/pdf', [PhoneDirectoryExportController::class, 'exportPdf'])->name('export.pdf');

    // Imports
    Route::get('/import', [PhoneDirectoryImportController::class, 'index'])->name('import.index');
    Route::get('/import/template', [PhoneDirectoryImportController::class, 'downloadTemplate'])->name('import.template');
    Route::post('/import', [PhoneDirectoryImportController::class, 'process'])->name('import.process');
    Route::get('/import/errors', [PhoneDirectoryImportController::class, 'downloadErrors'])->name('import.errors');

    // Departments & Categories Master Management
    Route::get('/departments', [PhoneDirectoryDepartmentController::class, 'index'])->name('departments.index');
    Route::post('/departments', [PhoneDirectoryDepartmentController::class, 'store'])->name('departments.store');
    Route::put('/departments/{department}', [PhoneDirectoryDepartmentController::class, 'update'])->name('departments.update');
    Route::delete('/departments/{department}', [PhoneDirectoryDepartmentController::class, 'destroy'])->name('departments.destroy');
    Route::post('/categories', [PhoneDirectoryDepartmentController::class, 'storeCategory'])->name('categories.store');
    Route::put('/categories/{category}', [PhoneDirectoryDepartmentController::class, 'updateCategory'])->name('categories.update');
    Route::delete('/categories/{category}', [PhoneDirectoryDepartmentController::class, 'destroyCategory'])->name('categories.destroy');
});
