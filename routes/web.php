<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

require __DIR__.'/modules/payment-public.php';

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    foreach (glob(__DIR__.'/modules/*.php') as $moduleRoutes) {
        if (str_ends_with($moduleRoutes, 'payment-public.php')) {
            continue;
        }
        require $moduleRoutes;
    }
});

Route::middleware(['auth', 'verified', 'role:super-admin'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users/{user}/permissions', [\App\Http\Controllers\UserPermissionController::class, 'update'])
        ->middleware('role:super-admin')
        ->name('users.permissions.update');
});
require __DIR__.'/auth.php';


