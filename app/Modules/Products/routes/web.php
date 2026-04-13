<?php

use App\Modules\Products\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'plan.feature:accounting'])
    ->prefix('products')
    ->name('products.')
    ->group(function () {
        Route::get('/', [ProductController::class, 'index'])->middleware('permission:products.view')->name('index');
        Route::get('/import', [ProductController::class, 'importPage'])->middleware('permission:products.create')->name('import-page');
        Route::get('/import/template/{format}', [ProductController::class, 'downloadTemplate'])->middleware('permission:products.create')->name('import-template');
        Route::post('/import', [ProductController::class, 'import'])->middleware('permission:products.create')->name('import');
        Route::get('/create', [ProductController::class, 'create'])->middleware('permission:products.create')->name('create');
        Route::post('/', [ProductController::class, 'store'])->middleware('permission:products.create')->name('store');
        Route::post('/bulk-action', [ProductController::class, 'bulkAction'])->middleware('permission:products.update')->name('bulk-action');
        Route::patch('/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->middleware('permission:products.toggle-status')->name('toggle-status');
        Route::get('/{product}', [ProductController::class, 'show'])->middleware('permission:products.view')->name('show');
        Route::get('/{product}/edit', [ProductController::class, 'edit'])->middleware('permission:products.update')->name('edit');
        Route::put('/{product}', [ProductController::class, 'update'])->middleware('permission:products.update')->name('update');
        Route::delete('/{product}', [ProductController::class, 'destroy'])->middleware('permission:products.delete')->name('destroy');
    });
