<?php

use App\Modules\Purchases\Http\Controllers\PurchaseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('purchases')
    ->name('purchases.')
    ->group(function () {
        Route::get('/', [PurchaseController::class, 'index'])->middleware('permission:purchases.view')->name('index');
        Route::get('/create', [PurchaseController::class, 'create'])->middleware('permission:purchases.create')->name('create');
        Route::post('/', [PurchaseController::class, 'store'])->middleware('permission:purchases.create')->name('store');
        Route::get('/{purchase}', [PurchaseController::class, 'show'])->middleware('permission:purchases.view')->name('show');
        Route::get('/{purchase}/edit', [PurchaseController::class, 'edit'])->middleware('permission:purchases.edit_draft')->name('edit');
        Route::put('/{purchase}', [PurchaseController::class, 'update'])->middleware('permission:purchases.edit_draft')->name('update');
        Route::post('/{purchase}/finalize', [PurchaseController::class, 'finalize'])->middleware('permission:purchases.finalize')->name('finalize');
        Route::get('/{purchase}/receive', [PurchaseController::class, 'receive'])->middleware('permission:purchases.receive')->name('receive');
        Route::post('/{purchase}/receive', [PurchaseController::class, 'storeReceipt'])->middleware('permission:purchases.receive')->name('receipts.store');
        Route::post('/{purchase}/cancel', [PurchaseController::class, 'cancel'])->middleware('permission:purchases.edit_draft')->name('cancel');
        Route::post('/{purchase}/void', [PurchaseController::class, 'void'])->middleware('permission:purchases.void')->name('void');
        Route::get('/{purchase}/print', [PurchaseController::class, 'print'])->middleware('permission:purchases.print')->name('print');
    });
