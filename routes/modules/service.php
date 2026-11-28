<?php

use App\Http\Controllers\Service\ServiceDashboardController;
use App\Http\Controllers\Service\ServiceOemCentreController;
use App\Http\Controllers\Service\ServiceReportController;
use App\Http\Controllers\Service\ServiceTicketController;
use Illuminate\Support\Facades\Route;

Route::prefix('service')->name('service.')->middleware('role_or_permission:super-admin|service-support.view|service-support.create|service-support.edit|service-support.manage|crm.view|crm.manage|hrms.view|hrms.manage')->group(function () {
    Route::get('/', [ServiceDashboardController::class, 'index'])->name('dashboard');
    Route::post('/escalate', [ServiceDashboardController::class, 'escalate'])->name('escalate');

    Route::get('/tickets', [ServiceTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [ServiceTicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [ServiceTicketController::class, 'store'])->name('tickets.store');
    Route::post('/oem-centres/quick-store', [ServiceTicketController::class, 'quickStoreOem'])->name('oem-centres.quick-store');
    Route::delete('/oem-centres/{oemCentre}/quick-destroy', [ServiceTicketController::class, 'quickDestroyOem'])->name('oem-centres.quick-destroy');
    Route::post('/technicians/quick-store', [ServiceTicketController::class, 'quickStoreTechnician'])->name('technicians.quick-store');
    Route::delete('/technicians/{employee}/quick-destroy', [ServiceTicketController::class, 'quickDestroyTechnician'])->name('technicians.quick-destroy');
    Route::get('/tickets/{ticket}', [ServiceTicketController::class, 'show'])->name('tickets.show');
    Route::get('/tickets/{ticket}/edit', [ServiceTicketController::class, 'edit'])->name('tickets.edit');
    Route::put('/tickets/{ticket}', [ServiceTicketController::class, 'update'])->name('tickets.update');
    Route::post('/tickets/{ticket}/assign', [ServiceTicketController::class, 'assign'])->name('tickets.assign');
    Route::post('/tickets/{ticket}/transition', [ServiceTicketController::class, 'transition'])->name('tickets.transition');
    Route::post('/tickets/{ticket}/visits', [ServiceTicketController::class, 'storeVisit'])->name('tickets.visits.store');
    Route::post('/tickets/{ticket}/installation', [ServiceTicketController::class, 'updateInstallation'])->name('tickets.installation');
    Route::post('/tickets/{ticket}/charges', [ServiceTicketController::class, 'updateCharges'])->name('tickets.charges');
    Route::post('/tickets/{ticket}/warranty', [ServiceTicketController::class, 'updateWarranty'])->name('tickets.warranty');
    Route::post('/tickets/{ticket}/feedback', [ServiceTicketController::class, 'feedback'])->name('tickets.feedback');
    Route::post('/tickets/{ticket}/invoice', [ServiceTicketController::class, 'createInvoice'])->name('tickets.invoice');
    Route::post('/tickets/{ticket}/attachments', [ServiceTicketController::class, 'storeAttachment'])->name('tickets.attachments');

    Route::get('/schedule', [ServiceTicketController::class, 'schedule'])->name('schedule');
    Route::get('/serial-history', [ServiceTicketController::class, 'serialHistory'])->name('serial-history');

    Route::get('/reports', [ServiceReportController::class, 'index'])->name('reports');
    Route::get('/warranty-register', [ServiceReportController::class, 'warrantyRegister'])->name('warranty-register');
    Route::get('/quotations', [ServiceReportController::class, 'quotations'])->name('quotations');
    Route::get('/feedback', [ServiceReportController::class, 'feedbackList'])->name('feedback');

    Route::get('/settings/oem-centres', [ServiceOemCentreController::class, 'index'])->name('oem-centres.index');
    Route::post('/settings/oem-centres', [ServiceOemCentreController::class, 'store'])->name('oem-centres.store');
    Route::put('/settings/oem-centres/{oemCentre}', [ServiceOemCentreController::class, 'update'])->name('oem-centres.update');
    Route::post('/settings/sla', [ServiceOemCentreController::class, 'updateSla'])->name('sla.update');
});
