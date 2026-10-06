<?php

use App\Http\Controllers\Hrms\AttendanceController;
use App\Http\Controllers\Hrms\DepartmentController;
use App\Http\Controllers\Hrms\DesignationController;
use App\Http\Controllers\Hrms\EmployeeController;
use App\Http\Controllers\Hrms\EmployeeDocumentController;
use App\Http\Controllers\Hrms\ExpenseClaimController;
use App\Http\Controllers\Hrms\LeaveBalanceController;
use App\Http\Controllers\Hrms\LeaveRequestController;
use App\Http\Controllers\Hrms\LeaveTypeController;
use App\Http\Controllers\Hrms\ManagerController;
use Illuminate\Support\Facades\Route;

Route::prefix('hrms')->name('hrms.')->middleware('role_or_permission:super-admin|hrms.view|hrms.create|hrms.edit|hrms.manage')->group(function () {
    // Employee Master
    Route::resource('employees', EmployeeController::class);
    Route::post('employees/{employee}/documents', [EmployeeDocumentController::class, 'store'])->name('employees.documents.store');
    Route::get('documents/{document}/download', [EmployeeDocumentController::class, 'download'])->name('documents.download');
    Route::delete('documents/{document}', [EmployeeDocumentController::class, 'destroy'])->name('documents.destroy');

    // Organization
    Route::resource('departments', DepartmentController::class)->except(['show']);
    Route::resource('designations', DesignationController::class)->except(['show']);
    Route::get('managers', [ManagerController::class, 'index'])->name('managers.index');

    // Attendance
    Route::get('attendances', [AttendanceController::class, 'index'])->name('attendances.index');
    Route::get('attendances/create', [AttendanceController::class, 'create'])->name('attendances.create');
    Route::post('attendances', [AttendanceController::class, 'store'])->name('attendances.store');
    Route::get('attendances/bulk', [AttendanceController::class, 'bulkCreate'])->name('attendances.bulk');
    Route::post('attendances/bulk', [AttendanceController::class, 'bulkStore'])->name('attendances.bulk.store');
    Route::post('attendances/punch', [AttendanceController::class, 'punch'])->name('attendances.punch');
    Route::get('attendances/{attendance}/edit', [AttendanceController::class, 'edit'])->name('attendances.edit');
    Route::put('attendances/{attendance}', [AttendanceController::class, 'update'])->name('attendances.update');

    // Leave Types & Balances
    Route::resource('leave-types', LeaveTypeController::class)->except(['show']);
    Route::resource('leave-balances', LeaveBalanceController::class)->only(['index', 'create', 'store']);

    // Leave Requests
    Route::get('leave-requests', [LeaveRequestController::class, 'index'])->name('leave-requests.index');
    Route::get('leave-requests/create', [LeaveRequestController::class, 'create'])->name('leave-requests.create');
    Route::post('leave-requests', [LeaveRequestController::class, 'store'])->name('leave-requests.store');
    Route::post('leave-requests/{leave_request}/approve', [LeaveRequestController::class, 'approve'])->name('leave-requests.approve');
    Route::post('leave-requests/{leave_request}/reject', [LeaveRequestController::class, 'reject'])->name('leave-requests.reject');
    Route::post('leave-requests/{leave_request}/cancel', [LeaveRequestController::class, 'cancel'])->name('leave-requests.cancel');

    // Expense Claims
    Route::get('expense-claims', [ExpenseClaimController::class, 'index'])->name('expense-claims.index');
    Route::get('expense-claims/create', [ExpenseClaimController::class, 'create'])->name('expense-claims.create');
    Route::post('expense-claims', [ExpenseClaimController::class, 'store'])->name('expense-claims.store');
    Route::post('expense-claims/{expense_claim}/approve', [ExpenseClaimController::class, 'approve'])->name('expense-claims.approve');
    Route::post('expense-claims/{expense_claim}/reject', [ExpenseClaimController::class, 'reject'])->name('expense-claims.reject');
    Route::post('expense-claims/{expense_claim}/settle', [ExpenseClaimController::class, 'settle'])->name('expense-claims.settle');
    Route::get('expense-claims/{expense_claim}/receipt', [ExpenseClaimController::class, 'downloadReceipt'])->name('expense-claims.receipt');
});
