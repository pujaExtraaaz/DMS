<?php

use App\Http\Controllers\Sales\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('invoices')->name('invoices.')->middleware('role:owner|super-admin|sales-manager|salesperson')->group(function () {
    Route::get('/', [InvoiceController::class, 'index'])->name('index');
    Route::get('/create', [InvoiceController::class, 'create'])->name('create');
    Route::post('/', [InvoiceController::class, 'store'])->name('store');
    Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');
    Route::get('/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('pdf');
    Route::post('/{invoice}/e-invoice', [InvoiceController::class, 'generateEInvoice'])->name('e-invoice');
    Route::post('/{invoice}/eway', [InvoiceController::class, 'generateEway'])->name('eway');
});
