<?php

use App\Http\Controllers\Catalog\BrandController;
use App\Http\Controllers\Catalog\CategoryController;
use App\Http\Controllers\Catalog\SubCategoryController;
use App\Http\Controllers\Organization\BranchController;
use App\Http\Controllers\Organization\BusinessGroupController;
use App\Http\Controllers\Organization\CompanyController;
use App\Http\Controllers\Organization\CompanyProfileController;
use App\Http\Controllers\Organization\FinancialYearController;
use App\Http\Controllers\Organization\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::prefix('organization')->name('organization.')->middleware('role_or_permission:super-admin|organization.view|organization.create|organization.edit|organization.manage')->group(function () {
    Route::get('company-profile', [CompanyProfileController::class, 'show'])->name('company-profile');
    Route::get('company-profile/logo', [CompanyProfileController::class, 'logo'])->name('company-profile.logo');
    Route::put('company-profile', [CompanyProfileController::class, 'update'])->name('company-profile.update');
    Route::resource('companies', CompanyController::class);
    Route::post('companies/{company}/set-default', [CompanyController::class, 'setDefault'])->name('companies.set-default');
    Route::resource('branches', BranchController::class)->except(['show']);
    Route::resource('warehouses', WarehouseController::class)->except(['show']);
    Route::resource('financial-years', FinancialYearController::class)->except(['show']);
    Route::post('financial-years/{financial_year}/set-current', [FinancialYearController::class, 'setCurrent'])->name('financial-years.set-current');
    Route::post('financial-years/{financial_year}/close', [FinancialYearController::class, 'close'])->name('financial-years.close');
    Route::post('financial-years/{financial_year}/reopen', [FinancialYearController::class, 'reopen'])->name('financial-years.reopen');
    Route::get('sister-concerns', [\App\Http\Controllers\Organization\SisterConcernController::class, 'dashboard'])->name('sister-concerns.dashboard');
    Route::get('sister-concerns/dashboard', [\App\Http\Controllers\Organization\SisterConcernController::class, 'dashboard'])->name('sister-concerns.dashboard.redirect');
    Route::get('sister-concerns/companies', [\App\Http\Controllers\Organization\SisterConcernController::class, 'companies'])->name('sister-concerns.companies');
    Route::get('sister-concerns/transactions', [\App\Http\Controllers\Organization\SisterConcernController::class, 'transactions'])->name('sister-concerns.transactions');
    Route::get('sister-concerns/reports', [\App\Http\Controllers\Organization\SisterConcernReportController::class, 'index'])->name('sister-concerns.reports');
    Route::get('sister-concerns/reports/export', [\App\Http\Controllers\Organization\SisterConcernReportController::class, 'export'])->name('sister-concerns.reports.export');
    Route::post('sister-concerns/transactions', [\App\Http\Controllers\Organization\InterCompanyTransactionController::class, 'store'])->name('sister-concerns.transactions.store');
    Route::get('sister-concerns/transactions/{transaction}', [\App\Http\Controllers\Organization\InterCompanyTransactionController::class, 'show'])->name('sister-concerns.transactions.show');
    Route::put('sister-concerns/transactions/{transaction}', [\App\Http\Controllers\Organization\InterCompanyTransactionController::class, 'update'])->name('sister-concerns.transactions.update');
    Route::delete('sister-concerns/transactions/{transaction}', [\App\Http\Controllers\Organization\InterCompanyTransactionController::class, 'destroy'])->name('sister-concerns.transactions.destroy');
    Route::resource('business-groups', BusinessGroupController::class);
});

Route::prefix('masters')->name('masters.')->middleware('role_or_permission:super-admin|masters.view|masters.create|masters.edit|masters.delete|masters.manage|products.view|products.create|products.edit|products.delete')->group(function () {
    Route::post('brands/quick-store', [BrandController::class, 'quickStore'])->name('brands.quick-store');
    Route::post('categories/quick-store', [CategoryController::class, 'quickStore'])->name('categories.quick-store');
    Route::post('sub-categories/quick-store', [SubCategoryController::class, 'quickStore'])->name('sub-categories.quick-store');
    Route::resource('brands', BrandController::class)->except(['show']);
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('sub-categories', SubCategoryController::class)->except(['show']);
});