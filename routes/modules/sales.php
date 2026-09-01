<?php

use App\Http\Controllers\Sales\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('invoices')->name('invoices.')->middleware('role_or_permission:super-admin|invoices.view|invoices.create|invoices.edit|sales.view|sales.create|sales.edit|sales.manage')->group(function () {
    Route::get('/', [InvoiceController::class, 'index'])->name('index');
    Route::get('/create', [InvoiceController::class, 'create'])->name('create');
    Route::post('/', [InvoiceController::class, 'store'])->name('store');
    Route::get('/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('pdf');
    Route::get('/{invoice}/e-invoice-preview', [InvoiceController::class, 'eInvoicePreview'])->name('e-invoice.preview');
    Route::get('/{invoice}/e-invoice', [InvoiceController::class, 'eInvoiceDocument'])->name('e-invoice.document');
    Route::get('/{invoice}/eway', [InvoiceController::class, 'eWayBillDocument'])->name('eway.document');
    Route::post('/{invoice}/e-invoice', [InvoiceController::class, 'generateEInvoice'])->name('e-invoice');
    Route::post('/{invoice}/eway', [InvoiceController::class, 'generateEway'])->name('eway');
    Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');
});
