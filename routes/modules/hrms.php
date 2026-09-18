<?php

use App\Http\Controllers\Hrms\ManagerController;
use App\Http\Controllers\Hrms\AttendanceController;
use App\Http\Controllers\Hrms\DepartmentController;
use App\Http\Controllers\Hrms\DesignationController;
use App\Http\Controllers\Hrms\EmployeeController;
use App\Http\Controllers\Hrms\ExpenseClaimController;
use App\Http\Controllers\Hrms\LeaveRequestController;
use App\Http\Controllers\Hrms\LeaveTypeController;
use Illuminate\Support\Facades\Route;

Route::prefix('hrms')->name('hrms.')->middleware('role_or_permission:super-admin|hrms.view|hrms.create|hrms.edit|hrms.manage')->group(function () {
    Route::resource('employees', EmployeeController::class);
    Route::resource('departments', DepartmentController::class)->except(['show']);
    Route::resource('designations', DesignationController::class)->except(['show']);
    Route::get('managers', [ManagerController::class, 'index'])->name('managers.index');

    Route::get('attendances', [AttendanceController::class, 'index'])->name('attendances.index');
    Route::get('attendances/create', [AttendanceController::class, 'create'])->name('attendances.create');
    Route::post('attendances', [AttendanceController::class, 'store'])->name('attendances.store');

    Route::resource('leave-types', LeaveTypeController::class)->except(['show']);

    Route::get('leave-requests', [LeaveRequestController::class, 'index'])->name('leave-requests.index');
    Route::get('leave-requests/create', [LeaveRequestController::class, 'create'])->name('leave-requests.create');
    Route::post('leave-requests', [LeaveRequestController::class, 'store'])->name('leave-requests.store');
    Route::post('leave-requests/{leave_request}/approve', [LeaveRequestController::class, 'approve'])->name('leave-requests.approve');
    Route::post('leave-requests/{leave_request}/reject', [LeaveRequestController::class, 'reject'])->name('leave-requests.reject');

    Route::get('expense-claims', [ExpenseClaimController::class, 'index'])->name('expense-claims.index');
    Route::get('expense-claims/create', [ExpenseClaimController::class, 'create'])->name('expense-claims.create');
    Route::post('expense-claims', [ExpenseClaimController::class, 'store'])->name('expense-claims.store');
    Route::post('expense-claims/{expense_claim}/approve', [ExpenseClaimController::class, 'approve'])->name('expense-claims.approve');
    Route::post('expense-claims/{expense_claim}/reject', [ExpenseClaimController::class, 'reject'])->name('expense-claims.reject');
});
