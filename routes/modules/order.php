<?php

use App\Http\Controllers\Order\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('orders')
    ->name('orders.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Order Listing
        |--------------------------------------------------------------------------
        */
        Route::get('/', [OrderController::class, 'index'])
            ->middleware(
                'role_or_permission:super-admin|orders.view|orders.manage'
            )
            ->name('index');

        /*
        |--------------------------------------------------------------------------
        | Order Creation
        |--------------------------------------------------------------------------
        */
        Route::get('/create', [OrderController::class, 'create'])
            ->middleware(
                'role_or_permission:super-admin|orders.create|orders.book|orders.manage'
            )
            ->name('create');

        Route::post('/', [OrderController::class, 'store'])
            ->middleware(
                'role_or_permission:super-admin|orders.create|orders.book|orders.manage'
            )
            ->name('store');

        /*
        |--------------------------------------------------------------------------
        | Search / Pricing
        |--------------------------------------------------------------------------
        */
        Route::get('/search/customers', [OrderController::class, 'searchCustomers'])
            ->middleware(
                'role_or_permission:super-admin|orders.view|orders.create|orders.book|orders.edit|orders.manage'
            )
            ->name('search.customers');

        Route::get('/search/products', [OrderController::class, 'searchProducts'])
            ->middleware(
                'role_or_permission:super-admin|orders.view|orders.create|orders.book|orders.edit|orders.manage'
            )
            ->name('search.products');

        Route::get('/search/uoms', [OrderController::class, 'searchUoms'])
            ->middleware(
                'role_or_permission:super-admin|orders.view|orders.create|orders.book|orders.edit|orders.manage'
            )
            ->name('search.uoms');

        Route::get('/resolve-price', [OrderController::class, 'resolvePrice'])
            ->middleware(
                'role_or_permission:super-admin|orders.view|orders.create|orders.book|orders.edit|orders.manage'
            )
            ->name('resolve-price');

        /*
        |--------------------------------------------------------------------------
        | Export / PDF
        |--------------------------------------------------------------------------
        */
        Route::get('/export', [OrderController::class, 'export'])
            ->middleware(
                'role_or_permission:super-admin|orders.view|orders.manage'
            )
            ->name('export');

        Route::get('/pdf', [OrderController::class, 'pdf'])
            ->middleware(
                'role_or_permission:super-admin|orders.view|orders.manage'
            )
            ->name('pdf');

        /*
        |--------------------------------------------------------------------------
        | Bulk Operations
        |--------------------------------------------------------------------------
        */
        Route::post('/bulk/approve', [OrderController::class, 'bulkApprove'])
            ->middleware(
                'role_or_permission:super-admin|orders.approve|orders.manage'
            )
            ->name('bulk.approve');

        Route::post('/bulk/convert', [OrderController::class, 'bulkConvert'])
            ->middleware(
                'role_or_permission:super-admin|orders.convert|orders.manage'
            )
            ->name('bulk.convert');

        /*
        |--------------------------------------------------------------------------
        | Individual Order
        |--------------------------------------------------------------------------
        */
        Route::get('/{order}/edit', [OrderController::class, 'edit'])
            ->middleware(
                'role_or_permission:super-admin|orders.edit|orders.manage'
            )
            ->name('edit');

        Route::put('/{order}', [OrderController::class, 'update'])
            ->middleware(
                'role_or_permission:super-admin|orders.edit|orders.manage'
            )
            ->name('update');

        Route::post('/{order}/approve', [OrderController::class, 'approve'])
            ->middleware(
                'role_or_permission:super-admin|orders.approve|orders.manage'
            )
            ->name('approve');

        Route::post('/{order}/convert', [OrderController::class, 'convert'])
            ->middleware(
                'role_or_permission:super-admin|orders.convert|orders.manage'
            )
            ->name('convert');

        Route::post('/{order}/cancel', [OrderController::class, 'cancel'])
            ->middleware(
                'role_or_permission:super-admin|orders.manage'
            )
            ->name('cancel');

        Route::get('/{order}', [OrderController::class, 'show'])
            ->middleware(
                'role_or_permission:super-admin|orders.view|orders.manage'
            )
            ->name('show');
    });