<?php

use App\Modules\Finance\Http\Controllers\FinanceCategoryController;
use App\Modules\Finance\Http\Controllers\FinanceTransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'plan.feature:commerce'])
    ->prefix('finance')
    ->name('finance.')
    ->group(function () {
        Route::get('/transactions', [FinanceTransactionController::class, 'index'])->middleware('permission:finance.view')->name('transactions.index');
        Route::get('/transactions/create', [FinanceTransactionController::class, 'create'])->middleware('permission:finance.create')->name('transactions.create');
        Route::post('/transactions', [FinanceTransactionController::class, 'store'])->middleware('permission:finance.create')->name('transactions.store');
        Route::get('/transactions/{transaction}', [FinanceTransactionController::class, 'show'])->middleware('permission:finance.view')->name('transactions.show');
        Route::get('/transactions/{transaction}/edit', [FinanceTransactionController::class, 'edit'])->middleware('permission:finance.create')->name('transactions.edit');
        Route::put('/transactions/{transaction}', [FinanceTransactionController::class, 'update'])->middleware('permission:finance.create')->name('transactions.update');
        Route::delete('/transactions/{transaction}', [FinanceTransactionController::class, 'destroy'])->middleware('permission:finance.create')->name('transactions.destroy');

        Route::get('/categories', [FinanceCategoryController::class, 'index'])->middleware('permission:finance.manage-categories')->name('categories.index');
        Route::post('/categories', [FinanceCategoryController::class, 'store'])->middleware('permission:finance.manage-categories')->name('categories.store');
        Route::get('/categories/{category}/edit', [FinanceCategoryController::class, 'edit'])->middleware('permission:finance.manage-categories')->name('categories.edit');
        Route::put('/categories/{category}', [FinanceCategoryController::class, 'update'])->middleware('permission:finance.manage-categories')->name('categories.update');
        Route::delete('/categories/{category}', [FinanceCategoryController::class, 'destroy'])->middleware('permission:finance.manage-categories')->name('categories.destroy');
    });
