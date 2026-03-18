<?php

use App\Modules\PointOfSale\Http\Controllers\BarcodeScanController;
use App\Modules\PointOfSale\Http\Controllers\HeldCartController;
use App\Modules\PointOfSale\Http\Controllers\PosCartController;
use App\Modules\PointOfSale\Http\Controllers\PosCartItemController;
use App\Modules\PointOfSale\Http\Controllers\PosScreenController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('pos')
    ->name('pos.')
    ->group(function () {
        Route::get('/', [PosScreenController::class, 'index'])->middleware('permission:pos.use')->name('index');
        Route::get('/architecture', [PosScreenController::class, 'architecture'])->middleware('permission:pos.use')->name('architecture');
        Route::get('/cart/active', [PosCartController::class, 'active'])->middleware('permission:pos.use')->name('cart.active');
        Route::patch('/cart/active', [PosCartController::class, 'update'])->middleware('permission:pos.use')->name('cart.update');
        Route::delete('/cart/active', [PosCartController::class, 'clear'])->middleware('permission:pos.use')->name('cart.clear');
        Route::post('/cart/items', [PosCartItemController::class, 'store'])->middleware('permission:pos.use')->name('cart.items.store');
        Route::patch('/cart/items/{item}', [PosCartItemController::class, 'update'])->middleware('permission:pos.use')->name('cart.items.update');
        Route::delete('/cart/items/{item}', [PosCartItemController::class, 'destroy'])->middleware('permission:pos.use')->name('cart.items.destroy');
        Route::post('/barcode/scan', [BarcodeScanController::class, 'store'])->middleware('permission:pos.use')->name('barcode.scan');
        Route::get('/held-carts', [HeldCartController::class, 'index'])->middleware('permission:pos.resume-cart')->name('held.index');
        Route::post('/held-carts', [HeldCartController::class, 'store'])->middleware('permission:pos.hold-cart')->name('held.store');
        Route::post('/held-carts/{cart}/resume', [HeldCartController::class, 'resume'])->middleware('permission:pos.resume-cart')->name('held.resume');
    });
