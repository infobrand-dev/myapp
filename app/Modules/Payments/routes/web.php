<?php

use App\Modules\Payments\Http\Controllers\PaymentController;
use App\Modules\Payments\Http\Controllers\PaymentMethodController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'plan.feature:accounting'])
    ->prefix('payments')
    ->name('payments.')
    ->group(function () {
        Route::get('/methods', [PaymentMethodController::class, 'index'])->middleware('permission:payments.manage_methods')->name('methods.index');
        Route::post('/methods', [PaymentMethodController::class, 'store'])->middleware('permission:payments.manage_methods')->name('methods.store');
        Route::get('/methods/{method}/edit', [PaymentMethodController::class, 'edit'])->middleware('permission:payments.manage_methods')->name('methods.edit');
        Route::put('/methods/{method}', [PaymentMethodController::class, 'update'])->middleware('permission:payments.manage_methods')->name('methods.update');
        Route::delete('/methods/{method}', [PaymentMethodController::class, 'destroy'])->middleware('permission:payments.manage_methods')->name('methods.destroy');

        Route::get('/', [PaymentController::class, 'index'])->middleware('permission:payments.view')->name('index');
        Route::get('/create', [PaymentController::class, 'create'])->middleware('permission:payments.create')->name('create');
        Route::post('/', [PaymentController::class, 'store'])->middleware('permission:payments.create')->name('store');
        Route::get('/{payment}', [PaymentController::class, 'show'])->middleware('permission:payments.view')->name('show');
        Route::post('/{payment}/void', [PaymentController::class, 'void'])->middleware('permission:payments.void')->name('void');
    });
