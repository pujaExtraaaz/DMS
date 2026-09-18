<?php

use App\Http\Controllers\Catalog\BrandController;
use App\Http\Controllers\Catalog\CategoryController;
use App\Http\Controllers\Catalog\SubCategoryController;
use App\Http\Controllers\Organization\BranchController;
use App\Http\Controllers\Organization\BusinessGroupController;
use App\Http\Controllers\Organization\CompanyController;
use App\Http\Controllers\Organization\FinancialYearController;
use App\Http\Controllers\Organization\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::prefix('organization')->name('organization.')->middleware('role_or_permission:super-admin|organization.view|organization.create|organization.edit|organization.manage')->group(function () {
    Route::resource('companies', CompanyController::class)->except(['show']);
    Route::resource('branches', BranchController::class)->except(['show']);
    Route::resource('warehouses', WarehouseController::class)->except(['show']);
    Route::resource('financial-years', FinancialYearController::class)->except(['show']);
    Route::post('financial-years/{financial_year}/set-current', [FinancialYearController::class, 'setCurrent'])->name('financial-years.set-current');
    Route::post('financial-years/{financial_year}/close', [FinancialYearController::class, 'close'])->name('financial-years.close');
    Route::post('financial-years/{financial_year}/reopen', [FinancialYearController::class, 'reopen'])->name('financial-years.reopen');
    Route::resource('business-groups', BusinessGroupController::class)->except(['show']);
});

Route::prefix('masters')->name('masters.')->middleware('role_or_permission:super-admin|masters.view|masters.create|masters.edit|masters.manage|products.view|products.create|products.edit')->group(function () {
    Route::resource('brands', BrandController::class)->except(['show']);
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('sub-categories', SubCategoryController::class)->except(['show']);
});
