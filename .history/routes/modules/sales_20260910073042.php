<?php

use App\Http\Controllers\Sales\InvoiceController;
use App\Http\Controllers\Sales\QuotationController;
use App\Http\Controllers\Sales\RegionBrandPolicyController;
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

Route::prefix('quotations')->name('quotations.')->middleware('role_or_permission:super-admin|quotations.view|quotations.create|quotations.edit|quotations.manage|sales.view|sales.create|sales.edit|sales.manage')->group(function () {
    Route::get('/', [QuotationController::class, 'index'])->name('index');
    Route::get('/create', [QuotationController::class, 'create'])->name('create');
    Route::post('/', [QuotationController::class, 'store'])->name('store');
    Route::get('/{quotation}', [QuotationController::class, 'show'])->name('show');
    Route::get('/{quotation}/edit', [QuotationController::class, 'edit'])->name('edit');
    Route::put('/{quotation}', [QuotationController::class, 'update'])->name('update');
    Route::post('/{quotation}/send', [QuotationController::class, 'send'])->name('send');
    Route::post('/{quotation}/accept', [QuotationController::class, 'accept'])->name('accept');
});

Route::prefix('region-policies')->name('region-policies.')->middleware('role_or_permission:super-admin|sales.manage|quotations.manage|masters.manage')->group(function () {
    Route::get('/', [RegionBrandPolicyController::class, 'index'])->name('index');
    Route::get('/create', [RegionBrandPolicyController::class, 'create'])->name('create');
    Route::post('/', [RegionBrandPolicyController::class, 'store'])->name('store');
    Route::get('/{region_policy}/edit', [RegionBrandPolicyController::class, 'edit'])->name('edit');
    Route::put('/{region_policy}', [RegionBrandPolicyController::class, 'update'])->name('update');
    Route::delete('/{region_policy}', [RegionBrandPolicyController::class, 'destroy'])->name('destroy');
});
