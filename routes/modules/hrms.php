<?php

use App\Http\Controllers\Hrms\AttendanceController;
use App\Http\Controllers\Hrms\AttendanceRegularisationController;
use App\Http\Controllers\Hrms\CompOffController;
use App\Http\Controllers\Hrms\DepartmentController;
use App\Http\Controllers\Hrms\DesignationController;
use App\Http\Controllers\Hrms\EmployeeController;
use App\Http\Controllers\Hrms\EmployeeDocumentController;
use App\Http\Controllers\Hrms\EsslSyncController;
use App\Http\Controllers\Hrms\ExpenseClaimController;
use App\Http\Controllers\Hrms\HolidayCalendarController;
use App\Http\Controllers\Hrms\HrmsDashboardController;
use App\Http\Controllers\Hrms\LeaveBalanceController;
use App\Http\Controllers\Hrms\LeaveEncashmentController;
use App\Http\Controllers\Hrms\LeaveManagementController;
use App\Http\Controllers\Hrms\LeavePolicyController;
use App\Http\Controllers\Hrms\LeaveRequestController;
use App\Http\Controllers\Hrms\LeaveTypeController;
use App\Http\Controllers\Hrms\ManagerController;
use App\Http\Controllers\Hrms\PayHeadController;
use App\Http\Controllers\Hrms\PayrollController;
use App\Http\Controllers\Hrms\PayrollRunController;
use App\Http\Controllers\Hrms\PunchConflictController;
use App\Http\Controllers\Hrms\SalaryStructureController;
use App\Http\Controllers\Hrms\ShiftController;
use Illuminate\Support\Facades\Route;

Route::prefix('hrms')->name('hrms.')->middleware('role_or_permission:super-admin|hrms.view|hrms.create|hrms.edit|hrms.manage')->group(function () {
    Route::get('dashboard', [HrmsDashboardController::class, 'index'])->name('dashboard');
    Route::get('reports/attendance-register', [HrmsDashboardController::class, 'attendanceRegister'])->name('reports.attendance-register');
    Route::get('reports/leave-utilisation', [HrmsDashboardController::class, 'leaveUtilisation'])->name('reports.leave-utilisation');
    Route::get('reports/late-ot', [HrmsDashboardController::class, 'lateOt'])->name('reports.late-ot');

    Route::match(['get', 'post'], 'employees/ifsc-lookup', [EmployeeController::class, 'ifscLookup'])->name('employees.ifsc-lookup');
    Route::resource('employees', EmployeeController::class);
    Route::get('employees/{employee}/photo', [EmployeeController::class, 'downloadPhoto'])->name('employees.photo');
    Route::get('employees/{employee}/cancelled-cheque', [EmployeeController::class, 'downloadCancelledCheque'])->name('employees.cancelled-cheque');
    Route::post('employees/{employee}/documents', [EmployeeDocumentController::class, 'store'])->name('employees.documents.store');
    Route::get('documents/{document}/download', [EmployeeDocumentController::class, 'download'])->name('documents.download');
    Route::delete('documents/{document}', [EmployeeDocumentController::class, 'destroy'])->name('documents.destroy');

    Route::get('payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('salary-structures/history/{employee}', [SalaryStructureController::class, 'history'])->name('salary-structures.history');
    Route::resource('salary-structures', SalaryStructureController::class)->except(['show']);
    Route::resource('pay-heads', PayHeadController::class)->except(['show']);
    Route::get('payroll-runs', [PayrollRunController::class, 'index'])->name('payroll-runs.index');
    Route::get('payroll-runs/create', [PayrollRunController::class, 'create'])->name('payroll-runs.create');
    Route::post('payroll-runs', [PayrollRunController::class, 'store'])->name('payroll-runs.store');
    Route::get('payroll-runs/{payroll_run}/department-report', [PayrollRunController::class, 'departmentReport'])->name('payroll-runs.department-report');
    Route::get('payroll-runs/{payroll_run}/employee-report', [PayrollRunController::class, 'employeeReport'])->name('payroll-runs.employee-report');
    Route::get('payroll-runs/{payroll_run}', [PayrollRunController::class, 'show'])->name('payroll-runs.show');
    Route::post('payroll-runs/{payroll_run}/recompute', [PayrollRunController::class, 'recompute'])->name('payroll-runs.recompute');
    Route::post('payroll-runs/{payroll_run}/approve', [PayrollRunController::class, 'approve'])->name('payroll-runs.approve');
    Route::post('payroll-runs/{payroll_run}/lock', [PayrollRunController::class, 'lock'])->name('payroll-runs.lock');
    Route::get('payroll-runs/{payroll_run}/bank-file', [PayrollRunController::class, 'bankFile'])->name('payroll-runs.bank-file');
    Route::get('payroll-runs/{payroll_run}/payslip/{line}', [PayrollRunController::class, 'payslip'])->name('payroll-runs.payslip');
    Route::get('payroll-runs/{payroll_run}/payslip/{line}/pdf', [PayrollRunController::class, 'payslipPdf'])->name('payroll-runs.payslip-pdf');

    Route::get('comp-off', [CompOffController::class, 'index'])->name('comp-off.index');
    Route::post('comp-off/credit', [CompOffController::class, 'credit'])->name('comp-off.credit');

    Route::get('punch-conflicts', [PunchConflictController::class, 'index'])->name('punch-conflicts.index');
    Route::post('punch-conflicts/resolve', [PunchConflictController::class, 'resolve'])->name('punch-conflicts.resolve');

    Route::post('departments/quick-store', [DepartmentController::class, 'quickStore'])->name('departments.quick-store');
    Route::resource('departments', DepartmentController::class)->except(['show']);
    Route::post('designations/quick-store', [DesignationController::class, 'quickStore'])->name('designations.quick-store');
    Route::resource('designations', DesignationController::class)->except(['show']);
    Route::get('managers', [ManagerController::class, 'index'])->name('managers.index');

    Route::get('holidays', [HolidayCalendarController::class, 'index'])->name('holidays.index');
    Route::get('holidays/create', [HolidayCalendarController::class, 'create'])->name('holidays.create');
    Route::post('holidays', [HolidayCalendarController::class, 'store'])->name('holidays.store');
    Route::get('holidays/{holiday}', [HolidayCalendarController::class, 'show'])->name('holidays.show');
    Route::get('holidays/{holiday}/edit', [HolidayCalendarController::class, 'edit'])->name('holidays.edit');
    Route::put('holidays/{holiday}', [HolidayCalendarController::class, 'update'])->name('holidays.update');
    Route::delete('holidays/{holiday}', [HolidayCalendarController::class, 'destroy'])->name('holidays.destroy');
    Route::post('holidays/{holiday}/publish', [HolidayCalendarController::class, 'publish'])->name('holidays.publish');
    Route::post('holidays/{holiday}/items', [HolidayCalendarController::class, 'storeHoliday'])->name('holidays.items.store');
    Route::put('holiday-items/{holidayItem}', [HolidayCalendarController::class, 'updateHoliday'])->name('holidays.items.update');
    Route::delete('holiday-items/{holidayItem}', [HolidayCalendarController::class, 'destroyHoliday'])->name('holidays.items.destroy');

    Route::get('attendances', [AttendanceController::class, 'index'])->name('attendances.index');
    Route::get('attendances/create', [AttendanceController::class, 'create'])->name('attendances.create');
    Route::post('attendances', [AttendanceController::class, 'store'])->name('attendances.store');
    Route::get('attendances/bulk', [AttendanceController::class, 'bulkCreate'])->name('attendances.bulk');
    Route::post('attendances/bulk', [AttendanceController::class, 'bulkStore'])->name('attendances.bulk.store');
    Route::post('attendances/punch', [AttendanceController::class, 'punch'])->name('attendances.punch');
    Route::get('attendances/{attendance}/edit', [AttendanceController::class, 'edit'])->name('attendances.edit');
    Route::put('attendances/{attendance}', [AttendanceController::class, 'update'])->name('attendances.update');

    Route::get('shifts', [ShiftController::class, 'index'])->name('shifts.index');
    Route::get('shifts/create', [ShiftController::class, 'create'])->name('shifts.create');
    Route::post('shifts', [ShiftController::class, 'store'])->name('shifts.store');
    Route::get('shifts/{shift}/edit', [ShiftController::class, 'edit'])->name('shifts.edit');
    Route::put('shifts/{shift}', [ShiftController::class, 'update'])->name('shifts.update');
    Route::delete('shifts/{shift}', [ShiftController::class, 'destroy'])->name('shifts.destroy');
    Route::post('shifts/assign', [ShiftController::class, 'assign'])->name('shifts.assign');

    Route::get('regularisations', [AttendanceRegularisationController::class, 'index'])->name('regularisations.index');
    Route::get('regularisations/create', [AttendanceRegularisationController::class, 'create'])->name('regularisations.create');
    Route::post('regularisations', [AttendanceRegularisationController::class, 'store'])->name('regularisations.store');
    Route::post('regularisations/{regularisation}/approve', [AttendanceRegularisationController::class, 'approve'])->name('regularisations.approve');
    Route::post('regularisations/{regularisation}/reject', [AttendanceRegularisationController::class, 'reject'])->name('regularisations.reject');

    Route::get('essl', [EsslSyncController::class, 'index'])->name('essl.index');
    Route::post('essl/pull', [EsslSyncController::class, 'pull'])->name('essl.pull');

    Route::get('leave-management', [LeaveManagementController::class, 'index'])->name('leave-management.index');
    Route::resource('leave-types', LeaveTypeController::class)->except(['show']);
    Route::resource('leave-balances', LeaveBalanceController::class)->only(['index', 'create', 'store']);
    Route::resource('leave-policies', LeavePolicyController::class)->except(['show']);
    Route::post('leave-policies/allocate', [LeavePolicyController::class, 'allocate'])->name('leave-policies.allocate');
    Route::get('leave-encashments', [LeaveEncashmentController::class, 'index'])->name('leave-encashments.index');
    Route::get('leave-encashments/create', [LeaveEncashmentController::class, 'create'])->name('leave-encashments.create');
    Route::post('leave-encashments', [LeaveEncashmentController::class, 'store'])->name('leave-encashments.store');
    Route::post('leave-encashments/{leave_encashment}/approve', [LeaveEncashmentController::class, 'approve'])->name('leave-encashments.approve');
    Route::post('leave-encashments/{leave_encashment}/reject', [LeaveEncashmentController::class, 'reject'])->name('leave-encashments.reject');

    Route::get('leave-requests', [LeaveRequestController::class, 'index'])->name('leave-requests.index');
    Route::get('leave-requests/create', [LeaveRequestController::class, 'create'])->name('leave-requests.create');
    Route::post('leave-requests', [LeaveRequestController::class, 'store'])->name('leave-requests.store');
    Route::post('leave-requests/{leave_request}/approve', [LeaveRequestController::class, 'approve'])->name('leave-requests.approve');
    Route::post('leave-requests/{leave_request}/reject', [LeaveRequestController::class, 'reject'])->name('leave-requests.reject');
    Route::post('leave-requests/{leave_request}/cancel', [LeaveRequestController::class, 'cancel'])->name('leave-requests.cancel');

    Route::get('expense-claims', [ExpenseClaimController::class, 'index'])->name('expense-claims.index');
    Route::get('expense-claims/create', [ExpenseClaimController::class, 'create'])->name('expense-claims.create');
    Route::post('expense-claims', [ExpenseClaimController::class, 'store'])->name('expense-claims.store');
    Route::post('expense-claims/{expense_claim}/approve', [ExpenseClaimController::class, 'approve'])->name('expense-claims.approve');
    Route::post('expense-claims/{expense_claim}/reject', [ExpenseClaimController::class, 'reject'])->name('expense-claims.reject');
    Route::post('expense-claims/{expense_claim}/settle', [ExpenseClaimController::class, 'settle'])->name('expense-claims.settle');
    Route::get('expense-claims/{expense_claim}/receipt', [ExpenseClaimController::class, 'downloadReceipt'])->name('expense-claims.receipt');
});
