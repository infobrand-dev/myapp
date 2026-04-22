<?php

use App\Modules\Purchases\Http\Controllers\PurchaseController;
use App\Modules\Purchases\Http\Controllers\PurchaseOrderController;
use App\Modules\Purchases\Http\Controllers\PurchaseRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'plan.feature:accounting', 'plan.feature:purchases'])
    ->prefix('purchases')
    ->name('purchases.')
    ->group(function () {
        Route::get('/', [PurchaseController::class, 'index'])->middleware('permission:purchases.view')->name('index');
        Route::get('/create', [PurchaseController::class, 'create'])->middleware('permission:purchases.create')->name('create');
        Route::post('/', [PurchaseController::class, 'store'])->middleware('permission:purchases.create')->name('store');
        Route::prefix('requests')
            ->name('requests.')
            ->group(function () {
                Route::get('/', [PurchaseRequestController::class, 'index'])->middleware('permission:purchase_request.view')->name('index');
                Route::get('/create', [PurchaseRequestController::class, 'create'])->middleware('permission:purchase_request.create')->name('create');
                Route::post('/', [PurchaseRequestController::class, 'store'])->middleware('permission:purchase_request.create')->name('store');
                Route::get('/{requestModel}', [PurchaseRequestController::class, 'show'])->middleware('permission:purchase_request.view')->name('show');
                Route::get('/{requestModel}/edit', [PurchaseRequestController::class, 'edit'])->middleware('permission:purchase_request.update_draft')->name('edit');
                Route::put('/{requestModel}', [PurchaseRequestController::class, 'update'])->middleware('permission:purchase_request.update_draft')->name('update');
                Route::post('/{requestModel}/status/{status}', [PurchaseRequestController::class, 'markStatus'])->middleware('permission:purchase_request.approve')->name('status');
                Route::post('/{requestModel}/convert', [PurchaseRequestController::class, 'convert'])->middleware('permission:purchase_request.convert')->name('convert');
            });
        Route::prefix('orders')
            ->name('orders.')
            ->group(function () {
                Route::get('/', [PurchaseOrderController::class, 'index'])->middleware('permission:purchase_order.view')->name('index');
                Route::get('/create', [PurchaseOrderController::class, 'create'])->middleware('permission:purchase_order.create')->name('create');
                Route::post('/', [PurchaseOrderController::class, 'store'])->middleware('permission:purchase_order.create')->name('store');
                Route::get('/{order}', [PurchaseOrderController::class, 'show'])->middleware('permission:purchase_order.view')->name('show');
                Route::get('/{order}/edit', [PurchaseOrderController::class, 'edit'])->middleware('permission:purchase_order.update_draft')->name('edit');
                Route::put('/{order}', [PurchaseOrderController::class, 'update'])->middleware('permission:purchase_order.update_draft')->name('update');
                Route::post('/{order}/status/{status}', [PurchaseOrderController::class, 'markStatus'])->middleware('permission:purchase_order.approve')->name('status');
                Route::post('/{order}/convert', [PurchaseOrderController::class, 'convert'])->middleware('permission:purchase_order.convert')->name('convert');
            });
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
