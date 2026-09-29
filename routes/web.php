<?php

use App\Http\Controllers\Organization\CompanyProfileController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

require __DIR__.'/modules/payment-public.php';
require __DIR__.'/modules/crm-public.php';
require __DIR__.'/modules/tally-connector-public.php';

Route::get('/invoice/qr/{type}/{token}', [
    \App\Http\Controllers\InvoiceQrController::class,
    'show',
])->name('invoice.qr');

Route::get('/eway-bill/qr/{token}', [
    \App\Http\Controllers\InvoiceQrController::class,
    'ewayBill',
])->name('eway-bill.qr');

// Direct Company Logo Stream Route (accessible across all domains and contexts)
Route::get('/companies/{company}/logo', [CompanyProfileController::class, 'logo'])->name('organization.companies.logo');

// Public storage fallback route (guarantees images render even if public/storage symlink is missing)
Route::get('/storage/{path}', function (string $path) {
    $filePath = storage_path('app/public/'.$path);
    if (! file_exists($filePath)) {
        abort(404);
    }

    return response()->file($filePath);
})->where('path', '.*')->name('storage.local');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    foreach (glob(__DIR__.'/modules/*.php') as $moduleRoutes) {
        if (str_ends_with($moduleRoutes, 'payment-public.php')
            || str_ends_with($moduleRoutes, 'crm-public.php')
            || str_ends_with($moduleRoutes, 'tally-connector-public.php')) {
            continue;
        }
        require $moduleRoutes;
    }
});

Route::middleware(['auth', 'verified', 'role:super-admin|client-admin'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users/{user}/permissions', [\App\Http\Controllers\UserPermissionController::class, 'update'])
        ->middleware('role:super-admin|client-admin')
        ->name('users.permissions.update');
});

require __DIR__.'/auth.php';