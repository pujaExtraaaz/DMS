<?php

use App\Http\Controllers\Sales\InvoiceController;
use App\Http\Controllers\Sales\QuotationController;
use App\Http\Controllers\Sales\RegionBrandPolicyController;
use Illuminate\Support\Facades\Route;

Route::prefix('invoices')->name('invoices.')->middleware('role_or_permission:super-admin|invoices.view|invoices.create|invoices.edit|sales.view|sales.create|sales.edit|sales.manage')->group(function () {
    Route::get('/', [InvoiceController::class, 'index'])->name('index');
    Route::get('/create', [InvoiceController::class, 'create'])->name('create');
    Route::post('/', [InvoiceController::class, 'store'])->name('store');
    Route::post('/draft', [InvoiceController::class, 'saveDraft'])->name('draft.save');
    Route::get('/customers/{customer}/sales-orders', [InvoiceController::class, 'customerSalesOrders'])->name('customer-sales-orders');
    Route::get('/sales-orders/{order}/data', [InvoiceController::class, 'salesOrderData'])->name('sales-order-data');
    Route::get('/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('pdf');
    Route::get('/{invoice}/preview', [InvoiceController::class, 'preview'])->name('preview');
    Route::get('/{invoice}/export/{format}', [InvoiceController::class, 'export'])->name('export');
    Route::get('/{invoice}/e-invoice-preview', [InvoiceController::class, 'eInvoicePreview'])->name('e-invoice.preview');
    Route::get('/{invoice}/e-invoice', [InvoiceController::class, 'eInvoiceDocument'])->name('e-invoice.document');
    Route::get('/{invoice}/eway', [InvoiceController::class, 'eWayBillDocument'])->name('eway.document');
    Route::get('/{invoice}/e-way-bill', [InvoiceController::class, 'eWayBillDocument'])->name('e-way-bill');
    Route::post('/{invoice}/e-invoice', [InvoiceController::class, 'generateEInvoice'])->name('e-invoice');
    Route::post('/{invoice}/e-invoice/generate', [InvoiceController::class, 'generateEInvoice'])->name('e-invoice.generate');
    Route::post('/{invoice}/e-invoice/cancel', [InvoiceController::class, 'cancelEInvoice'])->name('e-invoice.cancel');
    Route::post('/{invoice}/eway', [InvoiceController::class, 'generateEway'])->name('eway');
    Route::post('/{invoice}/eway/generate', [InvoiceController::class, 'generateEway'])->name('eway.generate');
    Route::post('/{invoice}/eway/cancel', [InvoiceController::class, 'cancelEway'])->name('eway.cancel');
    Route::post('/{invoice}/eway/update-vehicle', [InvoiceController::class, 'updateEwayVehicle'])->name('eway.update-vehicle');
    Route::get('/{invoice}/edit', [InvoiceController::class, 'edit'])->name('edit');
    Route::put('/{invoice}', [InvoiceController::class, 'update'])->name('update');
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
