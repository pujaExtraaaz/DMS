<?php

use App\Http\Controllers\Ess\EssPortalController;
use Illuminate\Support\Facades\Route;

Route::prefix('ess')->name('ess.')->middleware('auth')->group(function () {
    Route::get('/', [EssPortalController::class, 'home'])->name('home');
    Route::get('/holidays', [EssPortalController::class, 'holidays'])->name('holidays');
    Route::get('/leave', [EssPortalController::class, 'leave'])->name('leave');
    Route::post('/leave', [EssPortalController::class, 'storeLeave'])->name('leave.store');
    Route::get('/attendance', [EssPortalController::class, 'attendance'])->name('attendance');
    Route::post('/attendance/regularisation', [EssPortalController::class, 'storeRegularisation'])->name('regularisation.store');
    Route::get('/payslips', [EssPortalController::class, 'payslips'])->name('payslips');
    Route::get('/payslips/{line}', [EssPortalController::class, 'payslip'])->name('payslips.show');
    Route::get('/payslips/{line}/pdf', [EssPortalController::class, 'payslipPdf'])->name('payslips.pdf');

    Route::get('/approvals', [EssPortalController::class, 'approvals'])->name('approvals');
    Route::post('/approvals/leave/{leave_request}/approve', [EssPortalController::class, 'approveLeave'])->name('approvals.leave.approve');
    Route::post('/approvals/leave/{leave_request}/reject', [EssPortalController::class, 'rejectLeave'])->name('approvals.leave.reject');
    Route::post('/approvals/regularisations/{regularisation}/approve', [EssPortalController::class, 'approveRegularisation'])->name('approvals.regularisation.approve');
    Route::post('/approvals/regularisations/{regularisation}/reject', [EssPortalController::class, 'rejectRegularisation'])->name('approvals.regularisation.reject');
});
