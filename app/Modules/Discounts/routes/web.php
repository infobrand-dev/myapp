<?php

use App\Modules\Discounts\Http\Controllers\DiscountController;
use App\Modules\Discounts\Http\Controllers\DiscountEvaluationController;
use App\Modules\Discounts\Http\Controllers\DiscountUsageController;
use App\Modules\Discounts\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('discounts')
    ->name('discounts.')
    ->group(function () {
        Route::get('/', [DiscountController::class, 'index'])->middleware('permission:discounts.view')->name('index');
        Route::get('/create', [DiscountController::class, 'create'])->middleware('permission:discounts.create')->name('create');
        Route::post('/', [DiscountController::class, 'store'])->middleware('permission:discounts.create')->name('store');
        Route::get('/vouchers', [VoucherController::class, 'index'])->middleware('permission:discounts.manage-vouchers')->name('vouchers.index');
        Route::get('/usages', [DiscountUsageController::class, 'index'])->middleware('permission:discounts.view-usage')->name('usages.index');
        Route::post('/evaluate', [DiscountEvaluationController::class, 'evaluate'])->middleware('permission:discounts.evaluate')->name('evaluate');
        Route::post('/record-usage', [DiscountEvaluationController::class, 'record'])->middleware('permission:discounts.evaluate')->name('record-usage');
        Route::patch('/{discount}/toggle-status', [DiscountController::class, 'toggleStatus'])->middleware('permission:discounts.activate')->name('toggle-status');
        Route::post('/{discount}/archive', [DiscountController::class, 'archive'])->middleware('permission:discounts.archive')->name('archive');
        Route::get('/{discount}', [DiscountController::class, 'show'])->middleware('permission:discounts.view')->name('show');
        Route::get('/{discount}/edit', [DiscountController::class, 'edit'])->middleware('permission:discounts.update')->name('edit');
        Route::put('/{discount}', [DiscountController::class, 'update'])->middleware('permission:discounts.update')->name('update');
    });
