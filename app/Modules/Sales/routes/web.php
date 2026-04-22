<?php

use App\Modules\Sales\Http\Controllers\SaleController;
use App\Modules\Sales\Http\Controllers\SaleOrderController;
use App\Modules\Sales\Http\Controllers\SaleQuotationController;
use App\Modules\Sales\Http\Controllers\SaleReturnController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'plan.feature:accounting'])
    ->prefix('sales')
    ->name('sales.')
    ->group(function () {
        Route::get('/', [SaleController::class, 'index'])->middleware('permission:sales.view')->name('index');
        Route::get('/create', [SaleController::class, 'create'])->middleware('permission:sales.create')->name('create');
        Route::post('/', [SaleController::class, 'store'])->middleware('permission:sales.create')->name('store');
        Route::prefix('quotations')
            ->name('quotations.')
            ->group(function () {
                Route::get('/', [SaleQuotationController::class, 'index'])->middleware('permission:sales_quotation.view')->name('index');
                Route::get('/create', [SaleQuotationController::class, 'create'])->middleware('permission:sales_quotation.create')->name('create');
                Route::post('/', [SaleQuotationController::class, 'store'])->middleware('permission:sales_quotation.create')->name('store');
                Route::get('/{quotation}', [SaleQuotationController::class, 'show'])->middleware('permission:sales_quotation.view')->name('show');
                Route::get('/{quotation}/edit', [SaleQuotationController::class, 'edit'])->middleware('permission:sales_quotation.update_draft')->name('edit');
                Route::put('/{quotation}', [SaleQuotationController::class, 'update'])->middleware('permission:sales_quotation.update_draft')->name('update');
                Route::post('/{quotation}/status/{status}', [SaleQuotationController::class, 'markStatus'])->middleware('permission:sales_quotation.approve')->name('status');
                Route::post('/{quotation}/convert', [SaleQuotationController::class, 'convert'])->middleware('permission:sales_quotation.convert')->name('convert');
            });
        Route::prefix('orders')
            ->name('orders.')
            ->group(function () {
                Route::get('/', [SaleOrderController::class, 'index'])->middleware('permission:sales_order.view')->name('index');
                Route::get('/create', [SaleOrderController::class, 'create'])->middleware('permission:sales_order.create')->name('create');
                Route::post('/', [SaleOrderController::class, 'store'])->middleware('permission:sales_order.create')->name('store');
                Route::get('/{order}', [SaleOrderController::class, 'show'])->middleware('permission:sales_order.view')->name('show');
                Route::get('/{order}/edit', [SaleOrderController::class, 'edit'])->middleware('permission:sales_order.update_draft')->name('edit');
                Route::put('/{order}', [SaleOrderController::class, 'update'])->middleware('permission:sales_order.update_draft')->name('update');
                Route::post('/{order}/status/{status}', [SaleOrderController::class, 'markStatus'])->middleware('permission:sales_order.approve')->name('status');
                Route::post('/{order}/convert', [SaleOrderController::class, 'convert'])->middleware('permission:sales_order.convert')->name('convert');
            });
        Route::prefix('returns')
            ->name('returns.')
            ->group(function () {
                Route::get('/', [SaleReturnController::class, 'index'])->middleware('permission:sales_return.view')->name('index');
                Route::get('/create', [SaleReturnController::class, 'create'])->middleware('permission:sales_return.create')->name('create');
                Route::post('/', [SaleReturnController::class, 'store'])->middleware('permission:sales_return.create')->name('store');
                Route::get('/{saleReturn}', [SaleReturnController::class, 'show'])->middleware('permission:sales_return.view')->name('show');
                Route::post('/{saleReturn}/finalize', [SaleReturnController::class, 'finalize'])->middleware('permission:sales_return.finalize')->name('finalize');
                Route::post('/{saleReturn}/cancel', [SaleReturnController::class, 'cancel'])->middleware('permission:sales_return.cancel_draft')->name('cancel');
                Route::get('/{saleReturn}/print', [SaleReturnController::class, 'print'])->middleware('permission:sales_return.print')->name('print');
            });
        Route::get('/{sale}', [SaleController::class, 'show'])->middleware('permission:sales.view')->name('show');
        Route::get('/{sale}/edit', [SaleController::class, 'edit'])->middleware('permission:sales.update-draft')->name('edit');
        Route::put('/{sale}', [SaleController::class, 'update'])->middleware('permission:sales.update-draft')->name('update');
        Route::post('/{sale}/finalize', [SaleController::class, 'finalize'])->middleware('permission:sales.finalize')->name('finalize');
        Route::post('/{sale}/void', [SaleController::class, 'void'])->middleware('permission:sales.void')->name('void');
        Route::post('/{sale}/cancel', [SaleController::class, 'cancel'])->middleware('permission:sales.cancel-draft')->name('cancel');
        Route::get('/{sale}/invoice', [SaleController::class, 'invoice'])->middleware('permission:sales.print')->name('invoice');
    });
