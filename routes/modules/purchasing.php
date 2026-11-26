<?php

use App\Http\Controllers\Purchasing\CompanyDeliveryAddressController;
use App\Http\Controllers\Purchasing\DebitNoteController;
use App\Http\Controllers\Purchasing\FreightBillController;
use App\Http\Controllers\Purchasing\PurchaseComparisonController;
use App\Http\Controllers\Purchasing\LandedCostController;
use App\Http\Controllers\Purchasing\PurchaseInvoiceController;
use App\Http\Controllers\Purchasing\PurchaseInwardController;
use App\Http\Controllers\Purchasing\PurchaseOrderController;
use App\Http\Controllers\Purchasing\ReceiptNoteController;
use App\Http\Controllers\Purchasing\SupplierPaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('purchasing')->name('purchasing.')->middleware('role_or_permission:super-admin|purchases.view|purchases.create|purchases.edit|purchases.delete|purchases.manage|purchase-orders.view|purchase-orders.create|purchase-orders.edit|purchase-orders.delete|purchase-orders.manage|debit-notes.view|debit-notes.create|debit-notes.manage|supplier-payments.view|supplier-payments.create|supplier-payments.manage|inventory.view|inventory.manage')->group(function () {
    Route::get('comparisons', [PurchaseComparisonController::class, 'index'])->name('comparisons.index');
    Route::get('comparisons/create', [PurchaseComparisonController::class, 'create'])->name('comparisons.create');
    Route::post('comparisons', [PurchaseComparisonController::class, 'store'])->name('comparisons.store');
    Route::get('comparisons/suppliers/{customer}', [PurchaseComparisonController::class, 'supplier'])->name('comparisons.supplier');
    Route::get('comparisons/{comparison}/edit', [PurchaseComparisonController::class, 'edit'])->name('comparisons.edit');
    Route::put('comparisons/{comparison}', [PurchaseComparisonController::class, 'update'])->name('comparisons.update');
    Route::post('comparisons/{comparison}/submit', [PurchaseComparisonController::class, 'submit'])->name('comparisons.submit');
    Route::post('comparisons/{comparison}/approve', [PurchaseComparisonController::class, 'approve'])->name('comparisons.approve');
    Route::post('comparisons/{comparison}/reject', [PurchaseComparisonController::class, 'reject'])->name('comparisons.reject');
    Route::post('comparisons/{comparison}/convert', [PurchaseComparisonController::class, 'convert'])->name('comparisons.convert');
    Route::get('comparisons/{comparison}/print', [PurchaseComparisonController::class, 'print'])->name('comparisons.print');
    Route::get('comparisons/{comparison}/pdf', [PurchaseComparisonController::class, 'pdf'])->name('comparisons.pdf');
    Route::get('comparisons/{comparison}/excel', [PurchaseComparisonController::class, 'excel'])->name('comparisons.excel');
    Route::get('comparisons/{comparison}/files/{file}', [PurchaseComparisonController::class, 'file'])->name('comparisons.files');
    Route::get('comparisons/{comparison}', [PurchaseComparisonController::class, 'show'])->name('comparisons.show');

    Route::get('delivery-addresses', [CompanyDeliveryAddressController::class, 'index'])->name('delivery-addresses.index');
    Route::post('delivery-addresses', [CompanyDeliveryAddressController::class, 'store'])->name('delivery-addresses.store');

    Route::get('orders', [PurchaseOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/create', [PurchaseOrderController::class, 'create'])->name('orders.create');
    Route::post('orders', [PurchaseOrderController::class, 'store'])->name('orders.store');
    Route::get('orders/{order}/preview', [PurchaseOrderController::class, 'preview'])->name('orders.preview');
    Route::patch('orders/{order}/notes', [PurchaseOrderController::class, 'updateNotes'])->name('orders.notes');
    Route::get('orders/{order}/export/{format}', [PurchaseOrderController::class, 'export'])->name('orders.export');
    Route::get('orders/{order}', [PurchaseOrderController::class, 'show'])->name('orders.show');
    Route::get('orders/{order}/edit', [PurchaseOrderController::class, 'edit'])->name('orders.edit');
    Route::put('orders/{order}', [PurchaseOrderController::class, 'update'])->name('orders.update');
    Route::delete('orders/{order}', [PurchaseOrderController::class, 'destroy'])->name('orders.destroy');
    Route::post('orders/{order}/submit', [PurchaseOrderController::class, 'submit'])->name('orders.submit');
    Route::post('orders/{order}/approve', [PurchaseOrderController::class, 'approve'])->name('orders.approve');
    Route::post('orders/{order}/cancel', [PurchaseOrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('orders/{order}/receive', [PurchaseOrderController::class, 'receiveForm'])->name('orders.receive');
    Route::post('orders/{order}/receive', [PurchaseOrderController::class, 'receive'])->name('orders.receive.store');

    // Goods Receipts module removed — disabled routes return 404
    Route::get('inwards', [PurchaseInwardController::class, 'index'])->name('inwards.index');
    Route::get('inwards/{inward}', [PurchaseInwardController::class, 'show'])->name('inwards.show');

    // Receipt Notes
    Route::get('receipt-notes', [ReceiptNoteController::class, 'index'])->name('receipt-notes.index');
    Route::get('receipt-notes/create', [ReceiptNoteController::class, 'create'])->name('receipt-notes.create');
    Route::post('receipt-notes', [ReceiptNoteController::class, 'store'])->name('receipt-notes.store');
    Route::get('receipt-notes/purchase-order/{order}/items', [ReceiptNoteController::class, 'purchaseOrderItems'])->name('receipt-notes.po-items');
    Route::get('receipt-notes/{receiptNote}/preview', [ReceiptNoteController::class, 'preview'])->name('receipt-notes.preview');
    Route::get('receipt-notes/{receiptNote}/export/{format}', [ReceiptNoteController::class, 'export'])->name('receipt-notes.export');
    Route::get('receipt-notes/{receiptNote}', [ReceiptNoteController::class, 'show'])->name('receipt-notes.show');
    Route::get('receipt-notes/{receiptNote}/edit', [ReceiptNoteController::class, 'edit'])->name('receipt-notes.edit');
    Route::put('receipt-notes/{receiptNote}', [ReceiptNoteController::class, 'update'])->name('receipt-notes.update');
    Route::delete('receipt-notes/{receiptNote}', [ReceiptNoteController::class, 'destroy'])->name('receipt-notes.destroy');
    Route::post('receipt-notes/{receiptNote}/confirm', [ReceiptNoteController::class, 'confirm'])->name('receipt-notes.confirm');
    Route::post('receipt-notes/{receiptNote}/cancel', [ReceiptNoteController::class, 'cancel'])->name('receipt-notes.cancel');
    Route::get('receipt-notes/{receiptNote}/return', [ReceiptNoteController::class, 'returnForm'])->name('receipt-notes.return');
    Route::post('receipt-notes/{receiptNote}/return', [ReceiptNoteController::class, 'storeReturn'])->name('receipt-notes.return.store');

    Route::get('invoices', [PurchaseInvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/create', [PurchaseInvoiceController::class, 'create'])->name('invoices.create');
    Route::post('invoices', [PurchaseInvoiceController::class, 'store'])->name('invoices.store');
    Route::post('invoices/draft', [PurchaseInvoiceController::class, 'saveDraftSession'])->name('invoices.draft.save');
    Route::delete('invoices/draft', [PurchaseInvoiceController::class, 'clearDraftSession'])->name('invoices.draft.clear');

    Route::get('invoices/{invoice}/preview', [PurchaseInvoiceController::class, 'preview'])
        ->name('invoices.preview');
    Route::get('invoices/{invoice}/export/{format}', [PurchaseInvoiceController::class, 'export'])
        ->name('invoices.export');
    
    Route::get('invoices/purchase-order/{order}/data', [PurchaseInvoiceController::class, 'purchaseOrderData'])
        ->name('invoices.purchase-order-data');

    Route::get('invoices/receipt-note/{receiptNote}/data', [PurchaseInvoiceController::class, 'receiptNoteData'])
        ->name('invoices.receipt-note-data');

    Route::get('invoices/products/{product}/details', [PurchaseInvoiceController::class, 'productDetails'])
        ->name('invoices.product-details');

    Route::get('invoices/suppliers/{supplier}/purchase-orders', [PurchaseInvoiceController::class, 'supplierPurchaseOrders'])
        ->name('invoices.supplier-purchase-orders');

    Route::get('invoices/suppliers/{supplier}/receipt-notes', [PurchaseInvoiceController::class, 'supplierReceiptNotes'])
        ->name('invoices.supplier-receipt-notes');

    Route::get('invoices/{invoice}', [PurchaseInvoiceController::class, 'show'])
        ->name('invoices.show');
    Route::get('invoices/{invoice}/edit', [PurchaseInvoiceController::class, 'edit'])
        ->name('invoices.edit');
    Route::put('invoices/{invoice}', [PurchaseInvoiceController::class, 'update'])
        ->name('invoices.update');
    Route::delete('invoices/{invoice}', [PurchaseInvoiceController::class, 'destroy'])
        ->name('invoices.destroy');
    Route::post('invoices/{invoice}/post', [PurchaseInvoiceController::class, 'post'])
        ->name('invoices.post');
    Route::get('invoices/{invoice}/receive-material', [PurchaseInvoiceController::class, 'receiveMaterialForm'])
        ->name('invoices.receive-material');
    Route::post('invoices/{invoice}/receive-material', [PurchaseInvoiceController::class, 'receiveMaterial'])
        ->name('invoices.receive-material.store');
    Route::post('invoices/{invoice}/cancel', [PurchaseInvoiceController::class, 'cancel'])
        ->name('invoices.cancel');

    Route::get('landed-costs', [LandedCostController::class, 'index'])->name('landed-costs.index');
    Route::get('landed-costs/create', [LandedCostController::class, 'create'])->name('landed-costs.create');
    Route::post('landed-costs', [LandedCostController::class, 'store'])->name('landed-costs.store');
    Route::get('landed-costs/{landedCost}', [LandedCostController::class, 'show'])->name('landed-costs.show');

    Route::get('freight-bills', [FreightBillController::class, 'index'])->name('freight-bills.index');
    Route::get('freight-bills/create', [FreightBillController::class, 'create'])->name('freight-bills.create');
    Route::post('freight-bills', [FreightBillController::class, 'store'])->name('freight-bills.store');
    Route::get('freight-bills/{freightBill}', [FreightBillController::class, 'show'])->name('freight-bills.show');
    Route::get('freight-bills/{freightBill}/edit', [FreightBillController::class, 'edit'])->name('freight-bills.edit');
    Route::put('freight-bills/{freightBill}', [FreightBillController::class, 'update'])->name('freight-bills.update');
    Route::delete('freight-bills/{freightBill}', [FreightBillController::class, 'destroy'])->name('freight-bills.destroy');

    Route::get('debit-notes', [DebitNoteController::class, 'index'])->name('debit-notes.index');
    Route::get('debit-notes/create', [DebitNoteController::class, 'create'])->name('debit-notes.create');
    Route::post('debit-notes', [DebitNoteController::class, 'store'])->name('debit-notes.store');
    Route::get('debit-notes/{debitNote}', [DebitNoteController::class, 'show'])->name('debit-notes.show');
    Route::post('debit-notes/{debitNote}/approve', [DebitNoteController::class, 'approve'])->name('debit-notes.approve');
    Route::post('debit-notes/{debitNote}/post', [DebitNoteController::class, 'post'])->name('debit-notes.post');
    Route::post('debit-notes/{debitNote}/cancel', [DebitNoteController::class, 'cancel'])->name('debit-notes.cancel');

    Route::get('supplier-payments', [SupplierPaymentController::class, 'index'])->name('supplier-payments.index');
    Route::get('supplier-payments/create', [SupplierPaymentController::class, 'create'])->name('supplier-payments.create');
    Route::post('supplier-payments', [SupplierPaymentController::class, 'store'])->name('supplier-payments.store');
    Route::get('supplier-payments/{supplierPayment}', [SupplierPaymentController::class, 'show'])->name('supplier-payments.show');
    Route::post('supplier-payments/{supplierPayment}/cancel', [SupplierPaymentController::class, 'cancel'])->name('supplier-payments.cancel');
});
