<?php

use App\Modules\Sales\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('sales')
    ->name('sales.')
    ->group(function () {
        Route::get('/', [SaleController::class, 'index'])->middleware('permission:sales.view')->name('index');
        Route::get('/create', [SaleController::class, 'create'])->middleware('permission:sales.create')->name('create');
        Route::post('/', [SaleController::class, 'store'])->middleware('permission:sales.create')->name('store');
        Route::get('/{sale}', [SaleController::class, 'show'])->middleware('permission:sales.view')->name('show');
        Route::get('/{sale}/edit', [SaleController::class, 'edit'])->middleware('permission:sales.update-draft')->name('edit');
        Route::put('/{sale}', [SaleController::class, 'update'])->middleware('permission:sales.update-draft')->name('update');
        Route::post('/{sale}/finalize', [SaleController::class, 'finalize'])->middleware('permission:sales.finalize')->name('finalize');
        Route::post('/{sale}/void', [SaleController::class, 'void'])->middleware('permission:sales.void')->name('void');
        Route::post('/{sale}/cancel', [SaleController::class, 'cancel'])->middleware('permission:sales.cancel-draft')->name('cancel');
        Route::get('/{sale}/invoice', [SaleController::class, 'invoice'])->middleware('permission:sales.print')->name('invoice');
    });
